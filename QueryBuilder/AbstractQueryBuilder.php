<?php
namespace TFox\DbProcedureBundle\QueryBuilder;

use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Driver\Connection;
use TFox\DbProcedureBundle\Annotation\Parameter;
use TFox\DbProcedureBundle\Annotation\Procedure;
use TFox\DbProcedureBundle\Procedure\ProcedureInterface;

abstract class AbstractQueryBuilder
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ProcedureInterface
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

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(ProcedureInterface $procedure,
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
                    $this->readArgument($propertyAnnotation, $propertyReflection->getValue($this->procedure));
                    $propertyReflection->setAccessible(false);
                }
            }
        }

        foreach($this->procedureAnnotation->getCursors() as $cursorName) {
            $this->addArgument($cursorName, 'cursor', null);
        }
    }

    protected function readArgument(Parameter $parameterAnnotation, $value)
    {
        $this->addArgument($parameterAnnotation->getName(), $parameterAnnotation->getType(), $value);
    }

    protected function addArgument($name, $type, $value)
    {
        $this->arguments[] = array(
            'name' => $name,
            'type' => $this->translateArgumentType($type),
            'value' => $value
        );
    }

    protected abstract function translateArgumentType($type);

    protected abstract function buildQuery();

    protected abstract function executeQuery();
}