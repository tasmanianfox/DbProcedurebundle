<?php
namespace TFox\DbProcedureBundle\Service;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Common\Annotations\Reader;
use TFox\DbProcedureBundle\Annotation\Procedure;
use TFox\DbProcedureBundle\Procedure\ProcedureInterface;
use TFox\DbProcedureBundle\QueryBuilder\AbstractQueryBuilder;
use TFox\DbProcedureBundle\QueryBuilder\Oci8QueryBuilder;

/**
 * Executes procedures
 */
class ProcedureService
{

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @param RegistryInterface $doctrine
     * @param Reader $annotationReader
     */
    public function __construct(RegistryInterface $doctrine, Reader $annotationReader)
    {
        $this->doctrine = $doctrine;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param ProcedureInterface $procedure
     */
    public function execute(ProcedureInterface $procedure)
    {
        $classReflection = new \ReflectionClass($procedure);
        $classAnnotations = $this->annotationReader->getClassAnnotations($classReflection);
        foreach($classAnnotations as $classAnnotation) {
            if($classAnnotation instanceof Procedure) {
                $this->execProcedure($procedure, $classAnnotation);
            }
        }
    }

    /**
     * @param ProcedureInterface $procedure
     * @param Procedure $procedureAnnotation
     */
    private function execProcedure(ProcedureInterface $procedure, Procedure $procedureAnnotation)
    {
        $entityManagerName = $procedureAnnotation->getEntityManagerName();
        if(true == is_null($entityManagerName)) {
            $entityManagerName = $this->doctrine->getDefaultEntityManagerName();
        }
        $entityManager = $this->doctrine->getEntityManager($entityManagerName);
        $connection = $entityManager->getConnection();
        $driverName = $connection->getDriver()->getName();

        /* @var $queryBuilder AbstractQueryBuilder */
        $queryBuilder = null;
        if('oci8' == $driverName) {
            $queryBuilder = new Oci8QueryBuilder($connection);
        }
        if(true == is_null($queryBuilder)) {
            throw new \Exception(sprintf('Unsupported driver name: %s', $driverName));
        }
        $result = $queryBuilder->execute($procedure, $procedureAnnotation, $this->annotationReader);
        return $result;
    }
}