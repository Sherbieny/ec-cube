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

namespace Eccube\Tests\Web\Admin\Order;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Order;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mime\Email;

class MailControllerTest extends AbstractAdminWebTestCase
{
    use MailerAssertionsTrait;

    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * @var Order
     */
    protected $Order;

    protected function setUp(): void
    {
        parent::setUp();
        $faker = $this->getFaker();
        $this->Member = $this->createMember();
        $this->Customer = $this->createCustomer();
        $this->Order = $this->createOrder($this->Customer);

        $MailTemplate = new MailTemplate();
        $MailTemplate
            ->setName($faker->word)
            ->setMailSubject($faker->word)
            ->setCreator($this->Member);
        $this->entityManager->persist($MailTemplate);
        $this->entityManager->flush();
        for ($i = 0; $i < 3; $i++) {
            $this->MailHistories[$i] = new MailHistory();
            $this->MailHistories[$i]
                ->setOrder($this->Order)
                ->setSendDate(new \DateTime())
                ->setMailBody($faker->realText())
                ->setCreator($this->Member)
                ->setMailSubject('mail_subject-'.$i);

            $this->entityManager->persist($this->MailHistories[$i]);
            $this->entityManager->flush();
        }
    }

    public function createFormData()
    {
        $faker = $this->getFaker();

        return [
            'template' => 1,
            'mail_subject' => $faker->word,
            'tpl_data' => $faker->realText(),
            '_token' => 'dummy',
        ];
    }

    public function testIndex()
    {
        $this->client->request(
            'GET',
            $this->generateUrl('admin_order_mail', ['id' => $this->Order->getId()])
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testIndexWithConfirm()
    {
        $form = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('admin_order_mail', ['id' => $this->Order->getId()]),
            [
                'mail' => $form,
                'mode' => 'confirm',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testComplete()
    {
        $form = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('admin_order_mail', ['id' => $this->Order->getId()]),
            [
                'admin_order_mail' => $form,
                'mode' => 'complete',
            ]
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('admin_order_edit', ['id' => $this->Order->getId()])));

        $this->assertEmailCount(1);
        /** @var Email $Message */
        $Message = $this->getMailerMessage(0);

        $BaseInfo = $this->entityManager->find(BaseInfo::class, 1);
        $this->expected = '['.$BaseInfo->getShopName().'] '.$form['mail_subject'];
        $this->actual = $Message->getSubject();
        $this->verify();
    }

    /**
     * メールテンプレートを選択する
     *
     * @return void
     */
    public function testSelectMailTemplate()
    {
        $form = $this->createFormData();
        // 注文完了メール
        $form['template'] = 1;
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_order_mail', ['id' => $this->Order->getId()]),
            [
                'admin_order_mail' => $form,
                'mode' => 'change',
            ]
        );

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->actual = $crawler->filter('input[name="admin_order_mail[mail_subject]"]')->attr('value');
        $this->expected = 'ご注文ありがとうございます';
        $this->verify();

        // 会員仮登録完了メール
        $form['template'] = 2;
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_order_mail', ['id' => $this->Order->getId()]),
            [
                'admin_order_mail' => $form,
                'mode' => 'change',
            ]
        );

        $this->assertTrue($this->client->getResponse()->isOk());

        $this->actual = $crawler->filter('input[name="admin_order_mail[mail_subject]"]')->attr('value');
        $this->expected = '会員登録のご確認';
        $this->verify();
    }
}
