<?php
namespace TFox\DbProcedureBundle\Entity;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use TFox\DbProcedureBundle\Service\ProcedureService;

abstract class EntityRepository extends DoctrineEntityRepository
{

    /**
     * @var ProcedureService
     */
    protected $procedureService;

    public function setProcedureService(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }
}