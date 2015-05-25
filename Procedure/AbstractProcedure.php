<?php
namespace TFox\DbProcedureBundle\Procedure;

abstract class AbstractProcedure
{

    /**
     * Processes result of fetchCursor function.
     * Might be overridden if some extra-actions are necessary
     * @param $result
     * @return mixed
     */
    public function processFetchedResult($result)
    {
        return $result;
    }
}