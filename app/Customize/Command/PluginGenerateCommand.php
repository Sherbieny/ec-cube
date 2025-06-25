<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Command;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;

class PluginGenerateCommand extends Command
{
    protected static $defaultName = 'app:plugin:generate';

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var EccubeConfig
     */
    protected $config;

    /**
     * @var boolean
     */
    protected $isBundleAssets = false;

    public function __construct(EccubeConfig $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure()
    {
        $this
            ->addArgument('name', InputOption::VALUE_REQUIRED, 'plugin name')
            ->addArgument('code', InputOption::VALUE_REQUIRED, 'plugin code')
            ->addArgument('ver', InputOption::VALUE_REQUIRED, 'plugin version')
            ->setDescription('Generate plugin skeleton with better config table structure.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->fs = new Filesystem();
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('name') && null !== $input->getArgument('code') && null !== $input->getArgument('ver')) {
            return;
        }

        $this->io->title('EC-CUBE Plugin Generator Interactive Wizard');

        // Plugin name.
        $name = $input->getArgument('name');
        if (null !== $name) {
            $this->io->text(' > <info>name</info>: '.$name);
        } else {
            $name = $this->io->ask('name', 'EC-CUBE Sample Plugin');
            $input->setArgument('name', $name);
        }

        // Plugin code.
        $code = $input->getArgument('code');
        if (null !== $code) {
            $this->io->text(' > <info>code</info>: '.$code);
        } else {
            $code = $this->io->ask('code', 'Sample', [$this, 'validateCode']);
            $input->setArgument('code', $code);
        }

        // Plugin version.
        $version = $input->getArgument('ver');
        if (null !== $version) {
            $this->io->text(' > <info>ver</info>: '.$version);
        } else {
            $version = $this->io->ask('ver', '1.0.0', [$this, 'validateVersion']);
            $input->setArgument('ver', $version);
        }

        // Does the plugin need a bundle assets directory?
        $this->isBundleAssets = $this->io->confirm('Include JS/SCSS assets bundle?', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $code = $input->getArgument('code');
        $version = $input->getArgument('ver');

        $this->validateCode($code);
        $this->validateVersion($version);

        $pluginDir = $this->config->get('kernel.project_dir').'/app/Plugin/'.$code;

        $this->createDirectories($pluginDir);
        $this->createConfig($pluginDir, $name, $code, $version);
        $this->createEvent($pluginDir, $code);
        $this->createMessages($pluginDir);
        $this->createNav($pluginDir, $code);
        $this->createTwigBlock($pluginDir, $code);
        $this->createEntity($pluginDir, $code);
        $this->createRepository($pluginDir, $code);
        $this->createService($pluginDir, $code);
        $this->createFormType($pluginDir, $code);
        $this->createConfigController($pluginDir, $code);
        $this->createTwig($pluginDir, $code);
        $this->createGithubActions($pluginDir);

        if ($this->isBundleAssets) {
            $this->createAssetBundles($pluginDir, $code);
        }

        $this->io->success(sprintf('Plugin was successfully created: %s %s %s', $name, $code, $version));

        return 0;
    }

    public function validateCode($code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException('The code can not be empty.');
        }
        if (strlen($code) > 255) {
            throw new InvalidArgumentException('The code can enter up to 255 characters');
        }
        if (1 !== preg_match('/^\w+$/', $code)) {
            throw new InvalidArgumentException('The code [a-zA-Z_] is available.');
        }

        $pluginDir = $this->config->get('kernel.project_dir').'/app/Plugin/'.$code;
        if (file_exists($pluginDir)) {
            throw new InvalidArgumentException('Plugin directory exists.');
        }

        return $code;
    }

    public function validateVersion($version)
    {
        // TODO
        return $version;
    }

    /**
     * @param string $pluginDir
     */
    protected function createDirectories($pluginDir)
    {
        $dirs = [
            'Controller/Admin',
            'Entity',
            'Repository',
            'Service',
            'Form/Type',
            'Form/Extension',
            'Resource/doctrine',
            'Resource/locale',
            'Resource/template/admin',
            '.github/workflows',
        ];

        foreach ($dirs as $dir) {
            $this->fs->mkdir($pluginDir.'/'.$dir);
        }
    }

    /**
     * @param string $pluginDir
     * @param string $name
     * @param string $code
     * @param string $version
     */
    protected function createConfig($pluginDir, $name, $code, $version)
    {
        $config = <<<EOF
        {
            "name": "ec-cube/$code",
            "version": "$version",
            "description": "$name",
            "type": "eccube-plugin",
            "require": {
                "ec-cube/plugin-installer": "~0.0.7"
            },
            "extra": {
                "code": "$code"
            }
        }
        EOF;

        $this->fs->dumpFile($pluginDir.'/composer.json', $config);
    }

    /**
     * @param string $pluginDir
     */
    protected function createGithubActions($pluginDir)
    {
        $source = '
            name: Packaging for EC-CUBE Plugin
            on:
            release:
                types: [ published ]
            jobs:
            deploy:
                name: Build
                runs-on: ubuntu-18.04
                steps:
                - name: Checkout
                    uses: actions/checkout@v2
                - name: Packaging
                    working-directory: ../
                    run: |
                    rm -rf $GITHUB_WORKSPACE/.github
                    find $GITHUB_WORKSPACE -name "dummy" -delete
                    find $GITHUB_WORKSPACE -name ".git*" -and ! -name ".gitkeep" -print0 | xargs -0 rm -rf
                    chmod -R o+w $GITHUB_WORKSPACE
                    cd $GITHUB_WORKSPACE
                    tar cvzf ../${{ github.event.repository.name }}-${{ github.event.release.tag_name }}.tar.gz ./*
                - name: Upload binaries to release of TGZ
                    uses: svenstaro/upload-release-action@v1-release
                    with:
                    repo_token: ${{ secrets.GITHUB_TOKEN }}
                    file: ${{ runner.workspace }}/${{ github.event.repository.name }}-${{ github.event.release.tag_name }}.tar.gz
                    asset_name: ${{ github.event.repository.name }}-${{ github.event.release.tag_name }}.tar.gz
                    tag: ${{ github.ref }}
                    overwrite: true
            ';

        $this->fs->dumpFile($pluginDir.'/.github/workflows/release.yml', $source);
    }

    /**
     * @param string $pluginDir
     */
    protected function createMessages($pluginDir)
    {
        $this->fs->dumpFile($pluginDir.'/Resource/locale/messages.ja.yaml', '');
        $this->fs->dumpFile($pluginDir.'/Resource/locale/validators.ja.yaml', '');
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createEvent($pluginDir, $code)
    {
        $source = <<<EOF
        <?php

        namespace Plugin\\$code;

        use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

        class Event implements EventSubscriberInterface
        {
            /**
             * @return array
             */
            public static function getSubscribedEvents()
            {
                return [];
            }
        }
        EOF;

        $this->fs->dumpFile($pluginDir.'/Event.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createNav($pluginDir, $code)
    {
        $source = <<<EOL
            <?php

            namespace Plugin\\$code;

            use Eccube\\Common\\EccubeNav;

            class Nav implements EccubeNav
            {
                /**
                 * @return array
                 */
                public static function getNav()
                {
                    return [];
                }
            }

            EOL;

        $this->fs->dumpFile($pluginDir.'/Nav.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createTwigBlock($pluginDir, $code)
    {
        $source = <<<EOL
                <?php

                namespace Plugin\\$code;

                use Eccube\\Common\\EccubeTwigBlock;

                class TwigBlock implements EccubeTwigBlock
                {
                    /**
                     * @return array
                     */
                    public static function getTwigBlock()
                    {
                        return [];
                    }
                }

                EOL;

        $this->fs->dumpFile($pluginDir.'/TwigBlock.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createEntity($pluginDir, $code)
    {
        $snakecasedConfig = Container::underscore($code).'_config';

        $source = <<<EOL
                <?php

                namespace Plugin\\$code\\Entity;

                use Doctrine\\ORM\\Mapping as ORM;

                /**
                 * Config
                 *
                 * @ORM\\Table(name="plg_$snakecasedConfig")
                 * @ORM\\Entity(repositoryClass="Plugin\\$code\\Repository\\ConfigRepository")
                 */
                class Config
                {
                    const CONFIG_KEY_IS_ENABLED = 'is_enabled';

                    /**
                     * @var int
                     *
                     * @ORM\\Column(name="id", type="integer", options={"unsigned":true})
                     * @ORM\\Id
                     * @ORM\\GeneratedValue(strategy="IDENTITY")
                     */
                    private \$id;

                    /**
                     * @var string
                     *
                     * @ORM\\Column(name="config_key", type="string", length=255, nullable=false)
                     */
                    private \$configKey;

                    /**
                     * @var string
                     *
                     * @ORM\\Column(name="config_value", type="text", nullable=false)
                     */
                    private \$configValue;

                    public function getId(): int
                    {
                        return \$this->id;
                    }

                    public function getConfigKey(): string
                    {
                        return \$this->configKey;
                    }

                    public function setConfigKey(string \$configKey): self
                    {
                        \$this->configKey = \$configKey;
                        return \$this;
                    }

                    public function getConfigValue(): string
                    {
                        return \$this->configValue;
                    }

                    public function setConfigValue(string \$configValue): self
                    {
                        \$this->configValue = \$configValue;
                        return \$this;
                    }
                }

                EOL;

        $this->fs->dumpFile($pluginDir.'/Entity/Config.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createRepository($pluginDir, $code)
    {
        $source = <<<EOL
            <?php

            namespace Plugin\\$code\\Repository;

            use Doctrine\Persistence\ManagerRegistry;
            use Eccube\\Repository\\AbstractRepository;
            use Plugin\\$code\\Entity\\Config;

            /**
             * ConfigRepository
             *
             * This class was generated by the Doctrine ORM. Add your own custom
             * repository methods below.
             */
            class ConfigRepository extends AbstractRepository
            {
                /**
                 * ConfigRepository constructor.
                 *
                 * @param ManagerRegistry \$registry
                 */
                public function __construct(ManagerRegistry \$registry)
                {
                    parent::__construct(\$registry, Config::class);
                }

                /**
                 * @param int \$id
                 *
                 * @return null|Config
                 */
                public function get(\$id = 1)
                {
                    return \$this->find(\$id);
                }

                /**
                 * @param string \$key
                 *
                 * @return null|Config
                 */
                public function getByKey(\$key)
                {
                    return \$this->findOneBy(['configKey' => \$key]);
                }

                /**
                 * @param string \$key
                 *
                 * @return null|string
                 */
                public function getValueByKey(\$key)
                {
                    /** @var Config \$config */
                    \$config = \$this->findOneBy(['configKey' => \$key]);

                    return \$config ? \$config->getConfigValue() : null;
                }

                /**
                 * @param string \$key
                 * @param string \$value
                 */
                public function set(\$key, \$value)
                {
                    if (\$value === null) {
                        \$value = '';
                    }

                    if (is_bool(\$value)) {
                        \$value = \$value ? '1' : '0';
                    }

                    \$config = \$this->getByKey(\$key);
                    if (!\$config) {
                        \$config = new Config();
                        \$config->setConfigKey(\$key);
                    }

                    \$config->setConfigValue((string) \$value);
                    \$this->save(\$config);
                }
            }

            EOL;

        $this->fs->dumpFile($pluginDir.'/Repository/ConfigRepository.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createService($pluginDir, $code)
    {
        $source = <<<EOL
        <?php

        namespace Plugin\\$code\\Service;

        use Plugin\\$code\\Entity\\Config;
        use Plugin\\$code\\Repository\\ConfigRepository;

        /**
         * Class ConfigService
         * @package Plugin\\$code\\Service
         *
         * Handles all configuration related operations
         */
        class ConfigService
        {
            /**
             * @var ConfigRepository
             */
            private \$configRepository;

            public function __construct(ConfigRepository \$configRepository)
            {
                \$this->configRepository = \$configRepository;
            }

            /**
             * Get a configuration value by key
             *
             * @param string \$key
             * @return string|null
             */
            public function getConfigValue(string \$key): ?string
            {
                return \$this->configRepository->getValueByKey(\$key);
            }

            /**
             * Set a configuration value by key
             *
             * @param string \$key
             * @param string \$value
             * @return void
             */
            public function setConfigValue(string \$key, string \$value): void
            {
                \$this->configRepository->set(\$key, \$value);
            }

            /**
             * Get all configuration values
             *
             * @return array
             */
            public function getAllConfigValues(): array
            {
                return \$this->configRepository->findAll();
            }

            /**
             * Delete a configuration value by key
             *
             * @param string \$key
             * @return void
             */
            public function deleteConfigValue(string \$key): void
            {
                \$config = \$this->configRepository->findOneBy(['configKey' => \$key]);
                if (\$config) {
                    \$this->configRepository->delete(\$config);
                }
            }

            /**
             * Get the Plugin enabled status
             *
             * @return bool
             */
            public function isPluginEnabled(): bool
            {
                return (bool) \$this->getConfigValue(Config::CONFIG_KEY_IS_ENABLED);
            }

            /**
             * Set the Plugin enabled status
             *
             * @param string \$enabled
             * @return void
             */
            public function setPluginEnabled(string \$enabled): void
            {
                \$this->setConfigValue(Config::CONFIG_KEY_IS_ENABLED, \$enabled);
            }
        }

        EOL;

        $this->fs->dumpFile($pluginDir.'/Service/ConfigService.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createFormType($pluginDir, $code)
    {
        $source = <<<EOL
            <?php

            namespace Plugin\\$code\\Form\\Type\\Admin;

            use Plugin\\$code\\Entity\\Config;
            use Plugin\\$code\\Service\\ConfigService;
            use Symfony\\Component\\Form\\AbstractType;
            use Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType;
            use Symfony\\Component\\Form\\FormBuilderInterface;
            use Symfony\\Component\\Form\\FormEvent;
            use Symfony\\Component\\Form\\FormEvents;
            use Symfony\\Component\\OptionsResolver\\OptionsResolver;

            class ConfigType extends AbstractType
            {
                /**
                 * @var ConfigService
                 */
                private \$configService;

                public function __construct(ConfigService \$configService)
                {
                    \$this->configService = \$configService;
                }

                /**
                 * {@inheritdoc}
                 */
                public function buildForm(FormBuilderInterface \$builder, array \$options)
                {

                    \$builder->add(Config::CONFIG_KEY_IS_ENABLED, CheckboxType::class, [
                        'mapped' => false,
                        'required' => false,
                    ]);

                    // Form event listeners

                    \$builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent \$event) {
                        \$form = \$event->getForm();

                        \$form->get(Config::CONFIG_KEY_IS_ENABLED)->setData(\$this->configService->isPluginEnabled());
                    });

                }

                /**
                 * {@inheritdoc}
                 */
                public function configureOptions(OptionsResolver \$resolver)
                {
                    \$resolver->setDefaults([
                        'data_class' => null,
                    ]);
                }

            }

            EOL;

        $this->fs->dumpFile($pluginDir.'/Form/Type/Admin/ConfigType.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createConfigController($pluginDir, $code)
    {
        $snakecasedCode = Container::underscore($code);
        $snakecasedRouteName = Container::underscore($code).'_admin_config';

        $source = <<<EOL
            <?php

            namespace Plugin\\$code\\Controller\\Admin;

            use Eccube\\Controller\\AbstractController;
            use Plugin\\$code\\Entity\\Config;
            use Plugin\\$code\\Form\\Type\\Admin\\ConfigType;
            use Plugin\\$code\\Service\\ConfigService;
            use Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Template;
            use Symfony\\Component\\HttpFoundation\\Request;
            use Symfony\\Component\\Routing\\Annotation\\Route;

            class ConfigController extends AbstractController
            {
                /**
                * @Route("/%eccube_admin_route%/$snakecasedCode/config", name="$snakecasedRouteName")
                * @Template("@$code/admin/config.twig")
                */
                public function index(Request \$request, ConfigService \$configService)
                {
                    \$form = \$this->createForm(ConfigType::class);
                    \$form->handleRequest(\$request);

                    if (\$form->isSubmitted() && \$form->isValid()) {
                        // Save configuration values using ConfigService
                        \$configService->setConfigValue(Config::CONFIG_KEY_IS_ENABLED, \$form->get(Config::CONFIG_KEY_IS_ENABLED)->getData());

                        \$this->entityManager->flush();
                        \$this->addSuccess('登録しました。', 'admin');

                        return \$this->redirectToRoute('$snakecasedRouteName');
                    }

                    return [
                        'form' => \$form->createView(),
                    ];
                }
            }

            EOL;

        $this->fs->dumpFile($pluginDir.'/Controller/Admin/ConfigController.php', $source);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createTwig($pluginDir, $code)
    {
        $source = <<<EOL
            {% extends '@admin/default_frame.twig' %}

            {% set menus = ['store', 'plugin', 'plugin_list'] %}

            {% block title %}$code{% endblock %}
            {% block sub_title %}プラグイン一覧{% endblock %}

            {% form_theme form '@admin/Form/bootstrap_4_horizontal_layout.html.twig' %}

            {% block stylesheet %}{% endblock stylesheet %}

            {% block javascript %}{% endblock javascript %}

            {% block main %}
                <form role="form" method="post">

                    {{ form_widget(form._token) }}

                    <div class="c-contentsArea__cols">
                        <div class="c-contentsArea__primaryCol">
                            <div class="c-primaryCol">
                                <div class="card rounded border-0 mb-4">
                                    <div class="card-header"><span>設定</span></div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-3"><span>{{'common.enabled'|trans}}</span></div>
                                            <div class="col mb-2">
                                                {{ form_widget(form.is_enabled) }}
                                                {{ form_errors(form.is_enabled) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="c-conversionArea">
                        <div class="c-conversionArea__container">
                            <div class="row justify-content-between align-items-center">
                                <div class="col-6">
                                    <div class="c-conversionArea__leftBlockItem">
                                        <a class="c-baseLink"
                                            href="{{ url('admin_store_plugin') }}">
                                            <i class="fa fa-backward" aria-hidden="true"></i>
                                            <span>プラグイン一覧</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="row align-items-center justify-content-end">
                                        <div class="col-auto">
                                            <button class="btn btn-ec-conversion px-5"
                                                    type="submit">登録</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            {% endblock %}
            EOL;

        $this->fs->dumpFile($pluginDir.'/Resource/template/admin/config.twig', $source);
    }

    protected function createAssetBundles(string $pluginDir, string $code): void
    {
        $this->createJsAssetBundles($pluginDir, $code);
        $this->createScssAssetBundles($pluginDir);
    }

    /**
     * @param string $pluginDir
     * @param string $code
     */
    protected function createJsAssetBundles(string $pluginDir, string $code): void
    {
        $bundleFolder = 'bundle';
        $scopes = ['default', 'admin', 'shared'];

        foreach ($scopes as $scope) {
            $this->fs->mkdir("{$pluginDir}/Resource/assets/js/{$scope}/{$bundleFolder}");
        }

        $initializerTemplate = <<<JS
            /**
             * Plugin JS initializer for the %s scope.
             */
            const %sTest = require('./%s-test.js');

            document.addEventListener('DOMContentLoaded', () => {
                console.log('%sTest JS initialized');
                const testInstance = new %sTest();
                testInstance.testMethod();
            });
            JS;

        $testClassTemplate = <<<JS
            class %sTest {
                constructor() {
                    console.log('%sTest class initialized');
                    this.test = 'test';
                }
                testMethod() {
                    console.log('%sTest method called');
                }
            }

            module.exports = %sTest;
            JS;

        foreach ($scopes as $scope) {
            $jsDir = "{$pluginDir}/Resource/assets/js/{$scope}/{$bundleFolder}";
            $className = ucfirst($scope);

            // Write test class file
            $this->fs->dumpFile("{$jsDir}/{$scope}-test.js", sprintf(
                $testClassTemplate,
                $className,
                $className,
                $className,
                $className
            ));

            // Write initializer file
            $this->fs->dumpFile("{$jsDir}/initializer.js", sprintf(
                $initializerTemplate,
                ucfirst($scope),  // For comment
                $className,       // Variable name
                $scope,           // File path in require
                ucfirst($code),   // Plugin name for console log
                $className        // Class name
            ));
        }
    }

    protected function createScssAssetBundles(string $pluginDir)
    {
        $scssContent = <<<SCSS
        /**
         * Example SCSS file for plugin.
         * You can define your plugin-specific styles here.
         */
        .plugin-sample-style {
            color: red;
        }
        SCSS;

        $paths = [
            'Resource/assets/scss/admin/bundle',
            'Resource/assets/scss/default/bundle',
        ];

        foreach ($paths as $dir) {
            $this->fs->mkdir($pluginDir.'/'.$dir);
            $this->fs->dumpFile($pluginDir.'/'.$dir.'/example.scss', $scssContent);
        }
    }
}
