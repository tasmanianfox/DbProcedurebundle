<?php
namespace TFox\DbProcedureBundle\Annotation;

use Doctrine\Common\Annotations;
use Doctrine\ORM\Mapping\Annotation;

/**
 * Parameter for procedure
 * @Annotation
 * @Target({ "PROPERTY" })
 */
class Parameter implements Annotation
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $out = false;

    public function __construct(array $options = array())
    {
        if(true == array_key_exists('type', $options)) {
            $this->type = $options['type'];
        }
        if(true == array_key_exists('name', $options)) {
            $this->name = $options['name'];
        }
        if(true == array_key_exists('out', $options)) {
            $this->out = $options['out'];
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isOut()
    {
        return $this->out;
    }




}