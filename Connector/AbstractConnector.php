<?php
namespace TFox\DbProcedureBundle\Connector;

use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TFox\DbProcedureBundle\Annotation\Cursor;
use TFox\DbProcedureBundle\Annotation\Parameter;
use TFox\DbProcedureBundle\Annotation\Procedure;
use TFox\DbProcedureBundle\Procedure\AbstractProcedure;

abstract class AbstractConnector
{

    const PARAMETER_TYPE_STRING = 'STRING';
    const PARAMETER_TYPE_INTEGER = 'INTEGER';
    const PARAMETER_TYPE_CURSOR = 'CURSOR';
    const PARAMETER_TYPE_BLOB = 'BLOB';
    const PARAMETER_TYPE_DATE = 'DATE';
    const PARAMETER_TYPE_DATETIME = 'DATETIME';

    const FETCH_TYPE_ASSOC = 'assoc';
    const FETCH_TYPE_ARRAY = 'array';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var AbstractProcedure
     */
    protected $procedure;

    /**
     * @var Procedure
     */
    protected $procedureAnnotation;

    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var string
     */
    protected $querySql;

    /**
     * @var \Doctrine\DBAL\Statement
     */
    protected $statement;


    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;


    public function __construct(EventDispatcherInterface $eventDispatcher, Connection $connection)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->connection = $connection;
    }

    public function execute(AbstractProcedure $procedure,
        Procedure $procedureAnnotation, Reader $annotationReader)
    {
        $this->procedure = $procedure;
        $this->procedureAnnotation = $procedureAnnotation;
        $this->annotationReader = $annotationReader;
        $this->arguments = array();

        $this->readArguments();
        $this->buildQuery();
        $result = $this->executeQuery();
        return $result;
    }

    protected function readArguments()
    {
        $classReflection = new \ReflectionClass($this->procedure);
        $classProperties = $classReflection->getProperties();
        foreach($classProperties as $classProperty) {
            $propertyReflection = new \ReflectionProperty($this->procedure, $classProperty->getName());
            $propertyAnnotations = $this->annotationReader->getPropertyAnnotations($propertyReflection);
            foreach($propertyAnnotations as $propertyAnnotation) {
                if($propertyAnnotation instanceof Parameter) {
                    $propertyReflection->setAccessible(true);
                    $this->readArgument($propertyAnnotation, $propertyReflection->getValue($this->procedure),
                        $propertyReflection->getName());
                    $propertyReflection->setAccessible(false);
                } elseif($propertyAnnotation instanceof Cursor) {
                    $this->addArgument($propertyAnnotation->getName(), 'cursor', null, null, false);
                }
            }
        }

        foreach($this->procedureAnnotation->getCursors() as $cursorName) {
            $this->addArgument($cursorName, 'cursor', null, null, false);
        }
    }

    protected function readArgument(Parameter $parameterAnnotation, $value, $propertyName)
    {
        $this->addArgument($parameterAnnotation->getName(), $parameterAnnotation->getType(),
            $value, $propertyName, $parameterAnnotation->isOut());
    }

    protected function addArgument($name, $type, $value, $propertyName, $isOut)
    {
        $this->arguments[] = array(
            'name' => $name,
            'type' => $this->translateArgumentType($type),
            'value' => $value,
            'property' => $propertyName,
            'is_out' => $isOut
        );
    }

    protected abstract function translateArgumentType($type);

    protected abstract function buildQuery();

    protected abstract function executeQuery();

    public abstract function fetch($fetchType = self::FETCH_TYPE_ASSOC, $cursorName = null);

    public function cleanup() {}
}