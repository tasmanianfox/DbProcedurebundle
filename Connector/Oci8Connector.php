<?php
namespace TFox\DbProcedureBundle\Connector;


class Oci8Connector extends AbstractConnector
{

    const PARAMETER_TYPE_VARCHAR = 'VARCHAR';
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

    public function fetchCursor($cursorName)
    {
        if(false == array_key_exists($cursorName, $this->cursors)) {
            throw new \Exception(sprintf('Cursor "%s" not found', $cursorName));
        }
        $cursor = $this->cursors[$cursorName];
        $result = oci_fetch_assoc($cursor);
        return $result;
    }


    protected function translateArgumentType($type)
    {
        if(AbstractConnector::PARAMETER_TYPE_STRING == strtoupper($type)) {
            return self::PARAMETER_TYPE_VARCHAR;
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
            $argumentSqls[] = $this->formatArgument($argument['name']);
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
            $argumentName = $this->formatArgument($argument['name']);
            if(self::PARAMETER_TYPE_CURSOR == $argument['type']) {
                $this->cursors[$argument['name']] = oci_new_cursor($this->connectionResource);
                oci_bind_by_name($this->statementResource, $argumentName, $this->cursors[$argument['name']], - 1, $oracleType);

            } else {
                $this->values[$argument['name']] = $argument['value'];
                oci_bind_by_name($this->statementResource, $argumentName, $this->values[$argument['name']], - 1, $oracleType);
            }

        }
    }

    protected function executeQuery()
    {
        oci_execute($this->statementResource);
        foreach($this->cursors as $cursor) {
            oci_execute($cursor);
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

    private function formatArgument($argument)
    {
        return ':'.$argument;
    }

    public function cleanup()
    {
        foreach($this->cursors as $cursor) {
            oci_free_cursor($cursor);
        }
        oci_free_statement($this->statementResource);
    }
}