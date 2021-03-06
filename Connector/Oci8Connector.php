<?php
namespace TFox\DbProcedureBundle\Connector;

use Symfony\Component\PropertyAccess\PropertyAccess;
use TFox\DbProcedureBundle\Event\PostCursorFetchedEvent;
use TFox\DbProcedureBundle\TFoxDbProcedureEvents;

class Oci8Connector extends AbstractConnector
{

    const PARAMETER_TYPE_VARCHAR = 'VARCHAR';
    const PARAMETER_TYPE_NUMBER = 'NUMBER';
    const PARAMETER_TYPE_CLOB = 'CLOB';

    /**
     * PHP connection
     * @var resource
     */
    protected $connectionResource;

    /**
     * PHP statement
     * @var resource
     */
    protected $statementResource;

    /**
     * @var array
     */
    protected $values;

    /**
     * @var array
     */
    protected $cursors;

    public function getParameters()
    {
        return $this->values;
    }

    public function fetch($fetchType = self::FETCH_TYPE_ASSOC, $cursorName = null)
    {
        if(false == array_key_exists($cursorName, $this->cursors)) {
            throw new \Exception(sprintf('Cursor "%s" not found', $cursorName));
        }
        $cursor = $this->cursors[$cursorName];
        if(self::FETCH_TYPE_ASSOC == $fetchType) {
            $result = oci_fetch_assoc($cursor);
        } elseif(self::FETCH_TYPE_ARRAY == $fetchType) {
            $result = oci_fetch_array($cursor);
        } else {
            throw new \Exception(sprintf('Unsupported fetch type: %s', $fetchType));
        }
        if(true == is_array($result)) {
            foreach($result as $resultKey => $resultValue) {
                if(true == is_object($resultValue)) {
                    $result[$resultKey] = $resultValue->load();
                    $resultValue->free();
                }
            }
        }
        $result = $this->procedure->processFetchedResult($result);
        $event = new PostCursorFetchedEvent($this->procedure, $result);
        $this->eventDispatcher->dispatch(TFoxDbProcedureEvents::CURSOR_FETCHED_POST, $event);
        $result = $event->getResult();
        return $result;
    }


    protected function translateArgumentType($type)
    {
        if(AbstractConnector::PARAMETER_TYPE_STRING == strtoupper($type)) {
            return self::PARAMETER_TYPE_VARCHAR;
        } else if(AbstractConnector::PARAMETER_TYPE_INTEGER == strtoupper($type)) {
            return self::PARAMETER_TYPE_NUMBER;
        }
        return strtoupper($type);
    }

    protected function buildQuery()
    {
        $this->prepareSql();
        $this->bindParams();
    }

    private function prepareSql()
    {
        $argumentSqls = array();
        foreach($this->arguments as $argument) {
            $argumentSqls[] = $this->getArgumentSql($argument);
        }
        $argumentsSql = implode(', ', $argumentSqls);
        $this->querySql = sprintf('BEGIN %s.%s(%s); END;', $this->procedureAnnotation->getPackage(),
            $this->procedureAnnotation->getName(), $argumentsSql);

        if(false == $this->connection->isConnected()) {
            $this->connection->connect();
        }
        $dbalConnectionPropertyReflection = new \ReflectionProperty($this->connection, '_conn');
        $dbalConnectionPropertyReflection->setAccessible(true);
        $dbalConnection = $dbalConnectionPropertyReflection->getValue($this->connection);
        $dbalConnectionPropertyReflection->setAccessible(false);

        $connectionPropertyReflection = new \ReflectionProperty($dbalConnection, 'dbh');
        $connectionPropertyReflection->setAccessible(true);
        $this->connectionResource = $connectionPropertyReflection->getValue($dbalConnection);
        $connectionPropertyReflection->setAccessible(false);
        $this->statementResource = oci_parse($this->connectionResource, $this->querySql);
    }

    private function bindParams()
    {
        $this->values = array();
        $this->cursors  = array();
        foreach($this->arguments as $argument) {
            $oracleType = $this->getOracleType($argument['type']);
            $argumentName = $this->getBindKeyForArgument($argument);
            if(self::PARAMETER_TYPE_CURSOR == $argument['type']) {
                // Bind cursor
                $this->cursors[$argument['name']] = oci_new_cursor($this->connectionResource);
                oci_bind_by_name($this->statementResource, $argumentName, $this->cursors[$argument['name']], -1, $oracleType);
            } else if(true == in_array($argument['type'], array(AbstractConnector::PARAMETER_TYPE_BLOB, self::PARAMETER_TYPE_CLOB))) {
                // Bind LOB
                $this->values[$argument['name']] = oci_new_descriptor($this->connectionResource);
                oci_bind_by_name($this->statementResource, $argumentName, $this->values[$argument['name']], -1, $oracleType);
                if (false == is_null($argument['value'])) {
                    $this->values[$argument['name']]->writetemporary($argument['value']);
                }
            } else if(AbstractConnector::PARAMETER_TYPE_DATE == $argument['type'] && false == is_null($argument['value'])) {
                $date = $argument['value'];
                $this->values[$argument['name']] = $date->format('d-m-Y');
                oci_bind_by_name($this->statementResource, $argumentName, $this->values[$argument['name']]);
            } else if(AbstractConnector::PARAMETER_TYPE_CURSOR != $argument['type'] &&
                false == (is_null($argument['value']) && false == $argument['is_out'])) {
                // Bind other type of parameter
                $this->values[$argument['name']] = $argument['value'];
                if(true == is_null($argument['value'])) {
                    oci_bind_by_name($this->statementResource, $argumentName, $this->values[$argument['name']], $argument['max_length']);
                } else {
                    oci_bind_by_name($this->statementResource, $argumentName, $this->values[$argument['name']], -1, $oracleType);
                }
            }
        }
    }

    protected function executeQuery()
    {
        if(false == oci_execute($this->statementResource)) {
            throw new \Exception(sprintf('Failed to execute query: "%s"', $this->querySql));
        }
        foreach($this->cursors as $cursor) {
            oci_execute($cursor);
        }

        // Update values
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach($this->values as $valueKey => $value) {
            foreach($this->arguments as $argument) {
                if($argument['name'] == $valueKey) {
                    if(true == $accessor->isWritable($this->procedure, $argument['property'])) {
                        if(true == is_object($value)) {
                            $value = $value->load();
                        }
                        $accessor->setValue($this->procedure, $argument['property'], $value);
                    }
                }
            }

        }
    }


    private function getOracleType($type)
    {
        switch ($type) {
            case 'NUMBER' :
                return SQLT_INT;
            case 'VARCHAR' :
                return SQLT_CHR;
            case 'BLOB' :
                return OCI_B_BLOB;
            case 'CLOB' :
                return OCI_B_CLOB;
            case 'CURSOR':
                return OCI_B_CURSOR;
            default :
                return SQLT_CHR;
        }
    }

    /**
     * Creates a part of SQL-query for given argument
     * @param $argument
     * @return string
     */
    private function getArgumentSql($argument)
    {
        if(true == is_null($argument['value']) && AbstractConnector::PARAMETER_TYPE_CURSOR != $argument['type']
                && false == $argument['is_out']) {
            return 'NULL';
        } elseif(false == is_null($argument['value']) && AbstractConnector::PARAMETER_TYPE_DATE == $argument['type']) {
            return sprintf('TO_DATE(%s, \'dd-mm-yyyy\')', $this->getBindKeyForArgument($argument));
        }
        return $this->getBindKeyForArgument($argument);
    }

    /**
     * Prepares an argument name for uasge in oci_bind_by_name function
     * @param $argument
     * @return string
     */
    private function getBindKeyForArgument($argument)
    {
        return ':'.$argument['name'];
    }


    public function cleanup()
    {
        foreach($this->cursors as $cursor) {
            oci_free_cursor($cursor);
        }
        oci_free_statement($this->statementResource);
    }
}