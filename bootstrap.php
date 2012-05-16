<?php
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\ClassLoader\UniversalClassLoader;


use Doctrine\ORM\Tools\Setup,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\EventManager as EventManager,
    Doctrine\ORM\Events,
    Doctrine\ORM\Configuration,
    Doctrine\Common\Cache\ArrayCache as Cache,
    Doctrine\Common\Annotations\AnnotationRegistry, 
    Doctrine\Common\Annotations\AnnotationReader,
    DMS\Filter\Mapping,
    DMS\Filter\Filter,
    Doctrine\Common\ClassLoader;

require_once __DIR__.'/vendor/silex.phar';

require_once __DIR__.'/vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php';

$classLoaderSymfony = new \Doctrine\Common\ClassLoader('Symfony', __DIR__.'/vendor' );
$classLoaderSymfony->register(); 

$classLoaderDoctrineCommon = new \Doctrine\Common\ClassLoader('Doctrine\\Common', __DIR__.'/vendor/doctrine/common/lib' );
$classLoaderDoctrineCommon->register(); 

$classLoaderDoctrineMigrations = new \Doctrine\Common\ClassLoader('Doctrine\\DBAL\\Migrations', __DIR__.'/vendor/doctrine/dbal/lib' );
$classLoaderDoctrineMigrations->register(); 

$classLoaderDoctrineDbal = new \Doctrine\Common\ClassLoader('Doctrine\\DBAL', __DIR__.'/vendor/doctrine/dbal/lib' );
$classLoaderDoctrineDbal->register(); 

$classLoaderDoctrine = new \Doctrine\Common\ClassLoader('Doctrine', __DIR__.'/vendor/doctrine/orm/lib' );
$classLoaderDoctrine->register(); 

$classLoaderJMS = new \Doctrine\Common\ClassLoader('JMS', __DIR__.'/vendor' );
$classLoaderJMS->register(); 

$classLoaderDMS = new \Doctrine\Common\ClassLoader('DMS', __DIR__.'/vendor' );
$classLoaderDMS->register(); 

$classLoaderMetadata = new \Doctrine\Common\ClassLoader('Metadata', __DIR__.'/vendor/Metadata/src' );
$classLoaderMetadata->register(); 

$classLoaderLibrary = new \Doctrine\Common\ClassLoader('library', __DIR__ );
$classLoaderLibrary->register(); 

$classLoaderCoderockr = new \Doctrine\Common\ClassLoader('Coderockr', __DIR__.'/vendor' );
$classLoaderCoderockr->register();

$classLoaderModel = new \Doctrine\Common\ClassLoader('model', getenv('APPLICATION_PATH') . '/library' );
$classLoaderModel->register(); 

$classLoaderService = new \Doctrine\Common\ClassLoader('service', getenv('APPLICATION_PATH') . '/library' );
$classLoaderService->register(); 

$classLoaderTest = new \Doctrine\Common\ClassLoader('test', getenv('APPLICATION_PATH') . '/library' );
$classLoaderTest->register(); 



if(!getenv('APPLICATION_ENV')) 
    $env = 'testing';
else
    $env = getenv('APPLICATION_ENV');

if ($env == 'testing')
    include getenv('APPLICATION_PATH').'/configs/configs.testing.php';
elseif ($env == 'development')
    include getenv('APPLICATION_PATH').'/configs/configs.development.php';
else
    include getenv('APPLICATION_PATH').'/configs/configs.php';

//filter
//Get Doctrine Reader
$reader = new AnnotationReader();
//$reader->setEnableParsePhpImports(true);

//Load AnnotationLoader
$loader = new Mapping\Loader\AnnotationLoader($reader);

//Get a MetadataFactory
$metadataFactory = new Mapping\ClassMetadataFactory($loader);

//Get a Filter
$filter = new Filter($metadataFactory);

//doctrine
$config = new Configuration();
$cache = new Cache();
$config->setQueryCacheImpl($cache);
$config->setProxyDir('/tmp');
$config->setProxyNamespace('EntityProxy');
$config->setAutoGenerateProxyClasses(true);
 
//mapping (example uses annotations, could be any of XML/YAML or plain PHP)
AnnotationRegistry::registerFile(__DIR__.'/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
AnnotationRegistry::registerAutoloadNamespace('JMS', __DIR__.'/vendor');
AnnotationRegistry::registerAutoloadNamespace('DMS', __DIR__.'/vendor');

$driver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    new Doctrine\Common\Annotations\AnnotationReader(),
    array(__DIR__ .'/model')
);
$config->setMetadataDriverImpl($driver);
$config->setMetadataCacheImpl($cache);

//getting the EntityManager
$em = EntityManager::create(
    $dbOptions,
    $config
);


//load subscribers
$evm = $em->getEventManager();
try {
    $directoryIterator = new \DirectoryIterator(__DIR__ . '/model/subscriber');
    foreach ($directoryIterator as $f) {
        if ($f->getFileName() != '.' && $f->getFilename() !='..') {
            $subscriber = 'model\\subscriber\\' . $f->getBasename('.php');
            $evm->addEventSubscriber(new $subscriber);
        }
    }
}catch (UnexpectedValueException $e) {
    //directory doesn't exists
}
