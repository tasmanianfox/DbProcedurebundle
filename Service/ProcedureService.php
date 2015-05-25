<?php
namespace TFox\DbProcedureBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TFox\DbProcedureBundle\Annotation\Procedure;
use TFox\DbProcedureBundle\Connector\AbstractConnector;
use TFox\DbProcedureBundle\Connector\Oci8Connector;
use TFox\DbProcedureBundle\Entity\EntityRepository;
use TFox\DbProcedureBundle\Event\PostProcedureExecutedEvent;
use TFox\DbProcedureBundle\Procedure\AbstractProcedure;
use TFox\DbProcedureBundle\TFoxDbProcedureEvents;

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param RegistryInterface $doctrine
     * @param Reader $annotationReader
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RegistryInterface $doctrine, Reader $annotationReader,
        EventDispatcherInterface $eventDispatcher)
    {
        $this->doctrine = $doctrine;
        $this->annotationReader = $annotationReader;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param AbstractProcedure $procedure
     * @return $connector
     */
    public function execute(AbstractProcedure $procedure)
    {
        $classReflection = new \ReflectionClass($procedure);
        $classAnnotations = $this->annotationReader->getClassAnnotations($classReflection);
        /* @var $connector AbstractConnector */
        $connector = null;
        foreach($classAnnotations as $classAnnotation) {
            if($classAnnotation instanceof Procedure) {
                $connector = $this->execProcedure($procedure, $classAnnotation);
                break;
            }
        }
        return $connector;
    }

    /**
     * @param $entityClass
     * @param null $entityManagerName
     * @return EntityRepository
     * @throws \Exception
     */
    public function getRepository($entityClass, $entityManagerName = null)
    {
        $entityManager = $this->getEntityManagerByName($entityManagerName);
        /* @var $repository \TFox\DbProcedureBundle\Entity\EntityRepository */
        $repository = $entityManager->getRepository($entityClass);
        if(!$repository instanceof EntityRepository) {
            throw new \Exception(sprintf('A repository for entity %s is not an instance of \TFox\DbProcedureBundle\Entity\EntityRepository', $entityClass));
        }
        $repository->setProcedureService($this);
        return $repository;
    }

    /**
     * @param AbstractProcedure $procedure
     * @param Procedure $procedureAnnotation
     * @return AbstractConnector
     * @throws \Exception
     */
    private function execProcedure(AbstractProcedure $procedure, Procedure $procedureAnnotation)
    {
        $entityManagerName = $procedureAnnotation->getEntityManagerName();
        $entityManager = $this->getEntityManagerByName($entityManagerName);
        $connection = $entityManager->getConnection();
        $driverName = $connection->getDriver()->getName();

        /* @var $connector AbstractConnector */
        $connector = null;
        if('oci8' == $driverName) {
            $connector = new Oci8Connector($this->eventDispatcher, $connection);
        }
        if(true == is_null($connector)) {
            throw new \Exception(sprintf('Unsupported driver: %s', $driverName));
        }
        $connector->execute($procedure, $procedureAnnotation, $this->annotationReader);
        $this->eventDispatcher->dispatch(TFoxDbProcedureEvents::PROCEDURE_EXECUTED_POST,
            new PostProcedureExecutedEvent($procedure));
        return $connector;
    }

    /**
     * @param $entityManagerName
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManagerByName($entityManagerName)
    {
        if(true == is_null($entityManagerName)) {
            $entityManagerName = $this->doctrine->getDefaultEntityManagerName();
        }
        $entityManager = $this->doctrine->getEntityManager($entityManagerName);
        return $entityManager;
    }
}