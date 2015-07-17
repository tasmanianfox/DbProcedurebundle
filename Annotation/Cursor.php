<?php
namespace TFox\DbProcedureBundle\Annotation;

use Doctrine\Common\Annotations;
use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target({ "PROPERTY" })
 */
class Cursor implements Annotation
{

    /**
     * @var string
     */
    private $name;

    public function __construct(array $options = array())
    {
        $this->name = array_key_exists('name', $options) ? $options['name'] : null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}