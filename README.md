# DbProcedureBundle
This bundle helps to run stored routines in Symfony2. At the moment only support of Oracle database is implemented.

## Installation
1. To install a development version, add to composer.json the entry below:
```
"require": {
  ...
  "tfox/db-procedure-bundle": "1.*@dev"
}
```
Please check out link https://packagist.org/packages/tfox/db-procedure-bundle to view a list of stable versions.

2. Add to AppKernel.php the line below:
```
$bundles = array(
  ...
  new TFox\DbProcedureBundle\TFoxDbProcedureBundle(),
);
```
## Usage
In the first, a procedure should be defined:

```
<?php
namespace Acme\DemoBundle\Procedure;

use TFox\DbProcedureBundle\Annotation as DbProcedure;
use TFox\DbProcedureBundle\Procedure\ProcedureInterface;

/**
 * Declaration of the described function: demo_package.demo_procedure(some_id:NUMBER, contents:BLOB:OUT, ret_cursor:CURSOR)
 * @DbProcedure\Procedure(package="demo_package", name="demo_procedure", entity_manager="oracle_em", cursors={ "ret_cursor" })
 */
class DemoProcedure implements ProcedureInterface
{

    /**
     * @DbProcedure\Parameter(name="some_id", type="integer")
     * @var int
     */
    private $id;

    /**
     * @DbProcedure\Parameter(name="contents", type="blob")
     * @var string
     */
    private $content = null;


    public function __construct($id, $content)
    {
        $this->id = $id;
        $this->content = $content;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
```
An example code to call the defined procedure form controller:

```
<?php
namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Procedure\DemoProcedure;
use TFox\DbProcedureBundle\Connector\AbstractConnector;
use TFox\DbProcedureBundle\Connector\Oci8Connector;

class AcmeController extends Controller
{
    ...

    /**
     * @Sensio\Route("/test/{id}")
     * @param int $id
     * @return Response
     */
    public function testAction($id)
    {
        /** @var $service \TFox\DbProcedureBundle\Service\ProcedureService */
        $service = $this->get('db_procedure.service');
        /* @var $connector Oci8Connector */
        $docContent = '(empty)';
        $procedure = new DemoProcedure($id, $docContent);
        var_dump($procedure->getContent()); // Checking content variable before execution of the function
        $connector = $service->execute($procedure);
        var_dump($procedure->getContent()); // Content was changed here

        // Reading data from cursor
        while($result = $connector->fetch(AbstractConnector::FETCH_TYPE_ASSOC, 'ret_cursor')) {
            var_dump($result);
        }
        $connector->cleanup();
        return $this->render('AcmeDemoBundle::demo.html.twig');
    }
}
```

