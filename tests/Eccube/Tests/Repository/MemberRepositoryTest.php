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

namespace Eccube\Tests\Repository;

use Eccube\Entity\Master\Work;
use Eccube\Entity\Member;
use Eccube\Repository\MemberRepository;
use Eccube\Security\PasswordHasher\PasswordHasher;
use Eccube\Tests\EccubeTestCase;

/**
 * MemberRepository test cases.
 *
 * @author Kentaro Ohkouchi
 */
class MemberRepositoryTest extends EccubeTestCase
{
    /** @var Member */
    protected $Member;
    /** @var MemberRepository */
    protected $memberRepo;

    /** @var PasswordHasher */
    protected $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHasher = static::getContainer()->get(PasswordHasher::class);
        $this->memberRepo = $this->entityManager->getRepository(Member::class);
        $this->Member = $this->memberRepo->find(1);
        $Work = $this->entityManager->getRepository('Eccube\Entity\Master\Work')
            ->find(Work::ACTIVE);

        for ($i = 0; $i < 3; $i++) {
            $Member = new Member();
            $password = 'password';
            $password = $this->passwordHasher->hash($password);
            $Member
                ->setLoginId('member-1')
                ->setPassword($password)
                ->setSortNo($i)
                ->setWork($Work);
            $this->entityManager->persist($Member);
            $this->memberRepo->save($Member);
        }
    }

    public function testUp()
    {
        $sortNo = $this->Member->getSortNo();
        $this->memberRepo->up($this->Member);

        $this->expected = $sortNo + 1;
        $this->actual = $this->Member->getSortNo();
        $this->verify();
    }

    public function testUpWithException()
    {
        $this->expectException(\Exception::class);
        $this->Member->setSortNo(999);
        $this->entityManager->flush();

        $this->memberRepo->up($this->Member);
    }

    public function testDown()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $max = $qb->select('MAX(m.sort_no)')
            ->from(Member::class, 'm')
            ->getQuery()
            ->getSingleScalarResult();

        $this->Member->setSortNo($max + 1);
        $this->entityManager->flush();

        $sortNo = $this->Member->getSortNo();
        $this->memberRepo->down($this->Member);

        $this->expected = $sortNo - 1;
        $this->actual = $this->Member->getSortNo();
        $this->verify();
    }

    public function testDownWithException()
    {
        $this->expectException(\Exception::class);
        $this->Member->setSortNo(0);
        $this->entityManager->flush();

        $this->memberRepo->down($this->Member);
        $this->fail();
    }

    public function testSave()
    {
        $Member = new Member();
        $Member
            ->setLoginId('member-100')
            ->setPassword('password')
            ->setSalt('salt')
            ->setSortNo(100);

        $this->memberRepo->save($Member);

        // verify
        $member = $this->memberRepo->findOneBy(['login_id' => 'member-100']);
        $this->actual = $member->getPassword();
        $this->expected = $Member->getPassword();
        $this->verify();
    }

    public function testSaveWithSortNoNull()
    {
        $qb = $this->entityManager->createQueryBuilder();
        $sortNo = $qb->select('MAX(m.sort_no)')
            ->from(Member::class, 'm')
            ->getQuery()
            ->getSingleScalarResult();

        $Member = new Member();
        $Member
            ->setLoginId('member-100')
            ->setPassword('password')
            ->setSalt('salt')
            ->setSortNo(100);
        $this->memberRepo->save($Member);

        $this->expected = $sortNo + 1;
        $this->actual = $Member->getSortNo();

        $this->verify();
    }

    public function testDelete()
    {
        $Member = $this->createMember();
        $id = $Member->getId();
        $this->memberRepo->delete($Member);

        $Member = $this->memberRepo->find($id);
        $this->assertNull($Member);
    }

    public function testDeleteWithException()
    {
        if ($this->entityManager->getConnection()->getDatabasePlatform()->getName() == 'sqlite') {
            $this->markTestSkipped('Can not support for sqlite3');
        }

        $this->expectException(\Exception::class);
        $Member1 = $this->createMember();
        $Member2 = $this->createMember();
        $Member2->setCreator($Member1);
        $this->entityManager->flush();

        // 参照制約で例外となる
        $this->memberRepo->delete($Member1);
        $this->fail();
    }

    /**
     * https://github.com/EC-CUBE/ec-cube/issues/5119
     */
    public function testDeleteWithExceptionSelfForeignKey()
    {
        $Member1 = $this->createMember();
        $Member1->setCreator($Member1);
        $this->entityManager->flush();

        // 削除できることを確認
        $this->memberRepo->delete($Member1);
        self::assertNull($Member1->getId());
    }
}
