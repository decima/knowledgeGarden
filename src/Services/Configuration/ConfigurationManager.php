<?php

namespace App\Services\Configuration;

use App\Services\FileManager\FileExplorer;
use PhpCsFixer\Config;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

class ConfigurationManager
{

    private const string configFile = ".config.yaml";

    private ?Configuration $configuration = null;

    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface  $validator,
        private FileExplorer        $fileExplorer,
    )
    {
    }


    private function loadFromFile(): ?Configuration
    {
        $content = $this->fileExplorer->getFile(self::configFile, true);
        $config = $this->serializer->deserialize($content, Configuration::class, 'yml');
        $config->inMemory = false;
        $validation = $this->validator->validate($config);
        if ($validation->count() > 0) {
            throw new InvalidConfigurationException($validation);
        }
        return $config;
    }

    #[Required]
    public function initializeConfiguration(): Configuration
    {

        if ($this->configuration !== null) {
            return $this->configuration;
        }
        // load configuration from file
        try {
            $content = $this->loadFromFile();
            return $this->configuration = $content;
        } catch (IOException $exception) {

        } catch (InvalidConfigurationException $invalidConfigurationException) {
            $invalidConf = new Configuration();
            $invalidConf->stateAsString = $invalidConfigurationException->getMessage();
            return $invalidConf;
        }

        // or instantiate new "in memory"
        $this->configuration = new Configuration();
        $user = new User();
        $user->username = "admin";
        $user->permissions[] = "ROLE_ADMIN";
        $this->configuration->addUser($user);
        return $this->configuration;


    }

    public function __invoke(): Configuration
    {
        return $this->initializeConfiguration();
    }

    public function save()
    {
        $this->fileExplorer->writeContent(self::configFile, $this->serializer->serialize($this->configuration, 'yml', [YamlEncoder::YAML_INLINE => 100]), true);

    }


}