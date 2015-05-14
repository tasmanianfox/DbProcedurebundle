<?php
namespace TFox\DbProcedureBundle\QueryBuilder;

class Oci8QueryBuilder extends AbstractQueryBuilder
{


    protected function translateArgumentType($type)
    {
        if('string' == $type) {
            return 'varchar2';
        }
        return $type;
    }

    protected function buildQuery()
    {

    }

    protected function executeQuery()
    {

    }
}