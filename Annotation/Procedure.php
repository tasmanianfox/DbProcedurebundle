<?php
namespace TFox\DbProcedureBundle\Annotation;

use Doctrine\Common\Annotations;
use Doctrine\ORM\Mapping\Annotation;

/**
 * Procedure definitions should use this annotation
 * @Annotation
 * @Target({ "CLASS" })
 */
class Procedure implements Annotation
{

    /**
     * @var string
     */
    private $package;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $entityManagerName;

    /**
     * @var array
     */
    private $cursors;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if(true == array_key_exists('package', $options)) {
            $this->package = $options['package'];
        }
        if(true == array_key_exists('name', $options)) {
            $this->name = $options['name'];
        }
        if(true == array_key_exists('entity_manager', $options)) {
            $this->entityManagerName = $options['entity_manager'];
        }
        if(true == array_key_exists('cursors', $options)) {
            $this->cursors = $options['cursors'];
        } else {
            $this->cursors = array();
        }
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEntityManagerName()
    {
        return $this->entityManagerName;
    }

    /**
     * @return array
     */
    public function getCursors()
    {
        return $this->cursors;
    }


}