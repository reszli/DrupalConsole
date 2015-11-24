<?php

namespace Drupal\Console\Command;

abstract class ContainerAwareCommand extends Command
{
    private $services;

    private $events;

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     *   A ContainerInterface instance.
     */
    protected function getContainer()
    {
        return $this->getKernelHelper()->getKernel()->getContainer();
    }

    /**
     * @param bool $group
     *
     * @return array list of modules
     */
    public function getMigrations($tag = false)
    {
        $entity_manager = $this->getEntityManager();
        $migration_storage = $entity_manager->getStorage('migration');

        $entity_query_service = $this->getEntityQuery();
        $query = $entity_query_service->get('migration');

        if ($tag) {
            $query->condition('migration_tags.*', $tag);
        }

        $results = $query->execute();

        $migration_entities = $migration_storage->loadMultiple($results);

        $migrations = array();
        foreach ($migration_entities as $migration) {
            $migrations[$migration->id()]['tags'] = implode(', ', $migration->migration_tags);
            $migrations[$migration->id()]['description'] = ucwords($migration->label());
        }

        return $migrations;
    }

    public function getRestDrupalConfig()
    {
        return $this->getConfigFactory()
            ->get('rest.settings')->get('resources') ?: [];
    }

    /**
     * [geRest get a list of Rest Resouces].
     *
     * @param bool $rest_status return Rest Resources by status
     *
     * @return array list of rest resources
     */
    public function getRestResources($rest_status = false)
    {
        $config = $this->getRestDrupalConfig();

        $resourcePluginManager = $this->getPluginManagerRest();
        $resources = $resourcePluginManager->getDefinitions();

        $enabled_resources = array_combine(array_keys($config), array_keys($config));
        $available_resources = array('enabled' => array(), 'disabled' => array());

        foreach ($resources as $id => $resource) {
            $status = in_array($id, $enabled_resources) ? 'enabled' : 'disabled';
            $available_resources[$status][$id] = $resource;
        }

        // Sort the list of resources by label.
        $sort_resources = function ($resource_a, $resource_b) {
            return strcmp($resource_a['label'], $resource_b['label']);
        };
        if (!empty($available_resources['enabled'])) {
            uasort($available_resources['enabled'], $sort_resources);
        }
        if (!empty($available_resources['disabled'])) {
            uasort($available_resources['disabled'], $sort_resources);
        }

        if (isset($available_resources[$rest_status])) {
            return array($rest_status => $available_resources[$rest_status]);
        }

        return $available_resources;
    }

    public function getServices()
    {
        if (null === $this->services) {
            $this->services = [];
            $this->services = $this->getContainer()->getServiceIds();
        }

        return $this->services;
    }

    public function getEvents()
    {
        if (null === $this->events) {
            $this->events = [];
            $this->events = array_keys($this->getEventDispatcher()->getListeners());
        }

        return $this->events;
    }

    public function getRouteProvider()
    {
        return $this->getContainer()->get('router.route_provider');
    }

    /**
     * @param $rest
     * @param $rest_resources_ids
     * @param $translator
     *
     * @return mixed
     */
    public function validateRestResource($rest, $rest_resources_ids, $translator)
    {
        if (in_array($rest, $rest_resources_ids)) {
            return $rest;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    $translator->trans('commands.rest.disable.messages.invalid-rest-id'),
                    $rest
                )
            );
        }
    }

    /**
     * @return \Drupal\Core\Config\ConfigFactoryInterface
     */
    public function getConfigFactory()
    {
        return $this->getContainer()->get('config.factory');
    }

    /**
     * @return \Drupal\Core\State\StateInterface
     */
    public function getState()
    {
        return $this->getContainer()->get('state');
    }

    public function getConfigStorage()
    {
        return $this->getContainer()->get('config.storage');
    }

    /**
     * @return \Drupal\Core\Database\Connection
     */
    public function getDatabase()
    {
        return $this->getContainer()->get('database');
    }

    /**
     * @return \Drupal\Core\Datetime\DateFormatter;
     */
    public function getDateFormatter()
    {
        return $this->getContainer()->get('date.formatter');
    }

    /**
     * @return \Drupal\Core\Config\ConfigManagerInterface
     */
    public function getConfigManager()
    {
        return $this->getContainer()->get('config.manager');
    }

    /**
     * @return \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }

    public function getEntityManager()
    {
        return $this->getContainer()->get('entity.manager');
    }

    public function getCron()
    {
        return $this->getContainer()->get('cron');
    }

    /**
     * @return \Drupal\Core\ProxyClass\Lock\DatabaseLockBackend
     */
    public function getDatabaseLockBackend()
    {
        return $this->getContainer()->get('lock');
    }

    public function getViewDisplayManager()
    {
        return $this->getContainer()->get('plugin.manager.views.display');
    }

    public function getWebprofilerForms()
    {
        $profiler = $this->getContainer()->get('profiler');
        $tokens = $profiler->find(null, null, 1000, null, '', '');

        $forms = array();
        foreach ($tokens as $token) {
            $token = [$token['token']];
            $profile = $profiler->loadProfile($token);
            $formCollector = $profile->getCollector('forms');
            $collectedForms = $formCollector->getForms();
            if (empty($forms)) {
                $forms = $collectedForms;
            } elseif (!empty($collectedForms)) {
                $forms = array_merge($forms, $collectedForms);
            }
        }
        return $forms;
    }

    public function getEntityQuery()
    {
        return $this->getContainer()->get('entity.query');
    }

    public function getModuleInstaller()
    {
        return $this->getContainer()->get('module_installer');
    }

    public function getModuleHandler()
    {
        return $this->getContainer()->get('module_handler');
    }

    public function getPluginManagerRest()
    {
        return $this->getContainer()->get('plugin.manager.rest');
    }

    public function getContextRepository()
    {
        return $this->getContainer()->get('context.repository');
    }

    /**
     * getTestDiscovery return a service object for Simpletest.
     *
     * @return Drupal\simpletest\TestDiscovery
     */
    public function getTestDiscovery()
    {
        return $this->getContainer()->get('test_discovery');
    }

    public function getHttpClient()
    {
        return $this->getContainer()->get('http_client');
    }

    public function getSerializerFormats()
    {
        return $this->getContainer()->getParameter('serializer.formats');
    }

    public function getStringTanslation()
    {
        return $this->getContainer()->get('string_translation');
    }


    public function getAuthenticationProviders()
    {
        return $this->getContainer()->get('authentication_collector')->getSortedProviders();
    }

    /**
     * @return \Drupal\system\SystemManager
     */
    public function getSystemManager()
    {
        return $this->getContainer()->get('system.manager');
    }


    /**
     * @return array
     */
    public function getConnectionInfo()
    {
        return \Drupal\Core\Database\Database::getConnectionInfo();
    }

    /**
     * @return \Drupal\Core\Extension\ThemeHandlerInterface
     */
    public function getThemeHandler()
    {
        return $this->getContainer()->get('theme_handler');
    }

    /**
     * @return \Drupal\Core\Extension\ThemeHandlerInterface
     */
    public function getPassHandler()
    {
        return $this->getContainer()->get('password');
    }

    public function validateEventExist($event_name, $events = null)
    {
        if (!$events) {
            $events = $this->getEvents();
        }

        return $this->getValidator()->validateEventExist($event_name, $events);
    }

    public function validateModuleExist($module_name)
    {
        return $this->getValidator()->validateModuleExist($module_name);
    }

    public function validateServiceExist($service_name, $services = null)
    {
        if (!$services) {
            $services = $this->getServices();
        }

        return $this->getValidator()->validateServiceExist($service_name, $services);
    }

    public function validateModule($machine_name)
    {
        $machine_name = $this->validateMachineName($machine_name);
        $modules = $this->getSite()->getModules(false, false, true, true, true);
        if (in_array($machine_name, $modules)) {
            throw new \InvalidArgumentException(sprintf('Module "%s" already exist.', $machine_name));
        }

        return $machine_name;
    }

    public function validateModuleName($module_name)
    {
        return $this->getValidator()->validateModuleName($module_name);
    }

    public function validateModulePath($module_path, $create_dir = false)
    {
        return $this->getValidator()->validateModulePath($module_path, $create_dir);
    }

    public function validateClassName($class_name)
    {
        return $this->getValidator()->validateClassName($class_name);
    }

    public function validateMachineName($machine_name)
    {
        $machine_name = $this->getValidator()->validateMachineName($machine_name);

        if ($this->getEntityManager()->hasDefinition($machine_name)) {
            throw new \InvalidArgumentException(sprintf('Machine name "%s" is duplicated.', $machine_name));
        }

        return $machine_name;
    }

    public function validateSpaces($name)
    {
        return $this->getValidator()->validateSpaces($name);
    }

    public function removeSpaces($name)
    {
        return $this->getValidator()->removeSpaces($name);
    }

    public function generateEntity($entity_definition, $entity_type)
    {
        $entity_manager = $this->getEntityManager();
        $entity_storage = $entity_manager->getStorage($entity_type);
        $entity = $entity_storage->createFromStorageRecord($entity_definition);

        return $entity;
    }

    public function updateEntity($entity_id, $entity_type, $entity_definition)
    {
        $entity_manager = $this->getEntityManager();
        $entity_storage = $entity_manager->getStorage($entity_type);
        $entity = $entity_storage->load($entity_id);
        $entity_updated = $entity_storage->updateFromStorageRecord($entity, $entity_definition);

        return $entity_updated;
    }
}
