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

namespace Eccube\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Member;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoadDataFixturesEccubeCommand extends DoctrineCommand
{
    protected static $defaultName = 'eccube:fixtures:load';

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var UserPasswordHasherInterface
     */
    protected $passwordHasher;

    public function __construct(ManagerRegistry $registry, EccubeConfig $eccubeConfig, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry);
        $this->eccubeConfig = $eccubeConfig;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Load data fixtures to your database.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command loads data fixtures from EC-CUBE.

  <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager(null);

        // for full locale code cases
        $locale = env('ECCUBE_LOCALE', 'ja_JP');
        $locale = str_replace('_', '-', $locale);
        $locales = \Locale::parseLocale($locale);
        $localeDir = is_null($locales) ? 'ja' : $locales['language'];

        $loader = new \Eccube\Doctrine\Common\CsvDataFixtures\Loader();
        $loader->loadFromDirectory(__DIR__.'/../Resource/doctrine/import_csv/'.$localeDir);
        $executer = new \Eccube\Doctrine\Common\CsvDataFixtures\Executor\DbalExecutor($em);
        $fixtures = $loader->getFixtures();
        $executer->execute($fixtures);

        $login_id = env('ECCUBE_ADMIN_USER', 'admin');
        $login_password = env('ECCUBE_ADMIN_PASS', 'password');

        $password = $this->passwordHasher->hashPassword(new Member(), $login_password);

        $conn = $em->getConnection();
        $member_id = ('postgresql' === $conn->getDatabasePlatform()->getName())
            ? $conn->fetchOne("select nextval('dtb_member_id_seq')")
            : null;

        $conn->insert('dtb_member', [
            'id' => $member_id,
            'login_id' => $login_id,
            'password' => $password,
            // 'salt' => 'n/a',
            'work_id' => 1,
            'authority_id' => 0,
            'creator_id' => 1,
            'sort_no' => 1,
            'update_date' => new \DateTime(),
            'create_date' => new \DateTime(),
            'name' => trans('install.member_name'),
            'department' => 'EC-CUBE SHOP',
            'discriminator_type' => 'member',
        ], [
            'update_date' => \Doctrine\DBAL\Types\Types::DATETIMETZ_MUTABLE,
            'create_date' => \Doctrine\DBAL\Types\Types::DATETIMETZ_MUTABLE,
        ]);

        $shop_name = env('ECCUBE_SHOP_NAME', 'EC-CUBE SHOP');
        $admin_mail = env('ECCUBE_ADMIN_MAIL', 'admin@example.com');

        $id = ('postgresql' === $conn->getDatabasePlatform()->getName())
            ? $conn->fetchOne("select nextval('dtb_base_info_id_seq')")
            : null;

        $conn->insert('dtb_base_info', [
            'id' => $id,
            'shop_name' => $shop_name,
            'email01' => $admin_mail,
            'email02' => $admin_mail,
            'email03' => $admin_mail,
            'email04' => $admin_mail,
            'update_date' => new \DateTime(),
            'discriminator_type' => 'baseinfo',
            'option_mail_notifier' => true,
        ], [
            'update_date' => \Doctrine\DBAL\Types\Types::DATETIMETZ_MUTABLE,
        ]);

        $faviconPath = '/assets/img/common/favicon.ico';
        if (!file_exists($this->eccubeConfig->get('eccube_html_dir').'/user_data'.$faviconPath)) {
            $file = new Filesystem();
            $file->copy(
                $this->eccubeConfig->get('eccube_html_front_dir').$faviconPath,
                $this->eccubeConfig->get('eccube_html_dir').'/user_data'.$faviconPath
            );
        }

        $logoPath = '/assets/pdf/logo.png';
        if (!file_exists($this->eccubeConfig->get('eccube_html_dir').'/user_data'.$logoPath)) {
            $file = new Filesystem();
            $file->copy(
                $this->eccubeConfig->get('eccube_html_admin_dir').$logoPath,
                $this->eccubeConfig->get('eccube_html_dir').'/user_data'.$logoPath
            );
        }

        $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', 'Finished Successful!'));

        return 0;
    }
}
