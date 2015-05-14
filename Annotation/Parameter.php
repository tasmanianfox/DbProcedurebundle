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

    public function __construct(array $options = array())
    {
        if(true == array_key_exists('type', $options)) {
            $this->type = $options['type'];
        }
        if(true == array_key_exists('name', $options)) {
            $this->name = $options['name'];
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


}