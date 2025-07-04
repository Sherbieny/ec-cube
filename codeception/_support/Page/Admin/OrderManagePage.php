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

namespace Page\Admin;

class OrderManagePage extends AbstractAdminPageStyleGuide
{
    public static $検索条件_受注ステータス = ['id' => 'admin_search_order_status'];
    public static $検索結果_メッセージ = '#search_form #search_total_count';
    public static $検索結果_エラーメッセージ = '//*[@id="page_admin_order"]/div[1]/div[3]/div[3]/div/div/div[1]/div/div[1]';
    public static $詳細検索ボタン = '//*[@id="search_form"]/div[1]/div/div/div[3]/a/i';
    public static $タイトル要素 = '.c-container .c-contentsArea .c-pageTitle .c-pageTitle__titles';

    /**
     * OrderListPage constructor.
     */
    public function __construct(\AcceptanceTester $I)
    {
        parent::__construct($I);
    }

    public static function go(\AcceptanceTester $I)
    {
        $page = new self($I);

        return $page->goPage('/order', '受注一覧受注管理');
    }

    public static function at(\AcceptanceTester $I)
    {
        $page = new self($I);

        return $page->atPage('受注一覧受注管理');
    }

    public function 検索($value = '')
    {
        $this->tester->fillField(['id' => 'admin_search_order_multi'], $value);
        $this->tester->scrollTo('#search_submit', 0, -100);
        $this->tester->wait(1);
        $this->tester->click('#search_form #search_submit');
        $this->tester->wait(1); // XXX 画面遷移直後は selector の参照に失敗するため wait を入れる

        return $this;
    }

    public function 詳細検索設定()
    {
        $this->tester->click(self::$詳細検索ボタン);
        $this->tester->waitForElementVisible(['id' => 'searchDetail']);
        $this->tester->wait(0.5);

        return $this;
    }

    public function 入力_ご注文者お名前($value)
    {
        $this->tester->fillField(['id' => 'admin_search_order_name'], $value);

        return $this;
    }

    public function 入力_ご注文者お名前フリガナ($value)
    {
        $this->tester->fillField(['id' => 'admin_search_order_kana'], $value);

        return $this;
    }

    public function 詳細検索_電話番号($value = '')
    {
        $this->tester->click(self::$詳細検索ボタン);
        $this->tester->wait(1);
        $this->tester->fillField(['id' => 'admin_search_order_phone_number'], $value);
        $this->tester->click('#search_form #search_submit');

        return $this;
    }

    public function 受注CSVダウンロード実行()
    {
        $this->tester->click(['id' => 'csvDownloadDropDown']);
        $this->tester->waitForElementVisible(['id' => 'orderCsvDownload']);
        $this->tester->click(['id' => 'orderCsvDownload']);

        return $this;
    }

    public function 受注CSV出力項目設定()
    {
        $this->tester->click(['id' => 'csvSettingDropDown']);
        $this->tester->waitForElementVisible(['id' => 'orderCsvSetting']);
        $this->tester->click(['id' => 'orderCsvSetting']);

        return $this;
    }

    public function 配送CSVダウンロード実行()
    {
        $this->tester->click(['id' => 'csvDownloadDropDown']);
        $this->tester->waitForElementVisible(['id' => 'shippingCsvDownload']);
        $this->tester->click(['id' => 'shippingCsvDownload']);

        return $this;
    }

    public function 配送CSV出力項目設定()
    {
        $this->tester->click(['id' => 'csvSettingDropDown']);
        $this->tester->waitForElementVisible(['id' => 'shippingCsvSetting']);
        $this->tester->click(['id' => 'shippingCsvSetting']);

        return $this;
    }

    public function すべてチェック()
    {
        $this->tester->click('#form_bulk #toggle_check_all');

        return $this;
    }

    public function 要素をクリック($element)
    {
        $this->tester->click($element);

        return $this;
    }

    public function PDFフォームを入力($elId, $value)
    {
        $this->tester->fillField($elId, $value);

        return $this;
    }

    public function 一覧_編集($rowNum)
    {
        $this->tester->click("#search_result > tbody > tr:nth-child({$rowNum}) a.action-edit");

        return $this;
    }

    public function 一覧_削除()
    {
        $this->tester->click('#form_bulk > div.row.justify-content-between.mb-2 .btn-bulk-wrapper button.btn.btn-ec-delete');

        return $this;
    }

    public function Accept_削除()
    {
        $this->tester->waitForElementVisible(['id' => 'btn_bulk_delete']);
        $this->tester->click('#btn_bulk_delete');

        return $this;
    }

    public function Cancel_削除()
    {
        $this->tester->waitForElementVisible('#bulkDeleteModal > div > div > div.modal-footer > button.btn.btn-ec-sub');
        $this->tester->click('#bulkDeleteModal > div > div > div.modal-footer > button.btn.btn-ec-sub');

        return $this;
    }

    public function 一覧_メール通知($rowNum)
    {
        $this->tester->click(['css' => "#search_result > tbody > tr:nth-child({$rowNum}) > td.align-middle.pe-3 > div > div:nth-child(1) > a"]);
        $this->tester->waitForElementVisible(['id' => 'sentUpdateModal']);
        $this->tester->scrollTo(['id' => 'bulkChange']);
        $this->tester->click(['id' => 'bulkChange']);
        $this->tester->waitForElementVisible(['id' => 'bulkChangeComplete']);

        return $this;
    }

    public function 一覧_選択($rowNum)
    {
        $this->tester->checkOption(['css' => "#search_result > tbody > tr:nth-child({$rowNum}) > td > input[type=checkbox]"]);

        return $this;
    }

    public function 一覧_全選択()
    {
        $this->tester->checkOption('#toggle_check_all');

        return $this;
    }

    public function 個別メール送信($rowNum)
    {
        $this->tester->click(['css' => "#search_result > tbody > tr:nth-child({$rowNum}) > td.align-middle.pe-3.text-center > div > div:nth-child(1) > a"]);
        $this->tester->waitForElementVisible(['id' => 'sentUpdateModal']);
        $this->tester->scrollTo(['id' => 'bulkChange']);
        $this->tester->click(['id' => 'bulkChange']);
        $this->tester->waitForElementVisible(['id' => 'bulkChangeComplete']);

        return $this;
    }

    public function 一括メール送信()
    {
        $this->tester->click(['id' => 'bulkSendMail']);
        $this->tester->waitForElementVisible(['id' => 'sentUpdateModal']);
        $this->tester->click(['id' => 'bulkChange']);
        $this->tester->waitForElementVisible(['id' => 'bulkChangeComplete'], 30);

        return $this;
    }

    public function 一括メール送信_キャンセル()
    {
        $this->tester->wait(1);
        $this->tester->click(['id' => 'bulkSendMail']);
        $this->tester->waitForElementVisible(['id' => 'sentUpdateModal']);
        $this->tester->wait(1);
        $this->tester->click(['css' => '.modal.show .btn-ec-sub']);
        $this->tester->waitForElementNotVisible(['id' => 'sentUpdateModal'], 10);

        return $this;
    }

    public function 一覧_注文番号($rowNum)
    {
        return $this->tester->grabTextFrom("#search_result > tbody > tr:nth-child($rowNum) a.action-edit");
    }

    public function 受注ステータス検索($value = '')
    {
        $this->tester->checkOption(['id' => 'admin_search_order_status_'.$value]);
        $this->tester->click('#search_form #search_submit');

        return $this;
    }

    public function 受注ステータス変更($option = [])
    {
        $this->tester->selectOption('#option_bulk_status', $option);
        $this->tester->click('#form_bulk #btn_bulk_status');

        return $this;
    }

    public function 出荷済にする($rowNum)
    {
        $this->tester->wait(0.1); // 画面遷移直後は selector の参照に失敗するため wait を入れる
        $this->tester->waitForElementVisible(['id' => 'search_result']);
        $this->tester->scrollTo('#search_result');
        $this->tester->wait(1);
        $this->tester->click("#search_result > tbody > tr:nth-child($rowNum) a[data-type='status']");
        $this->tester->waitForElementVisible(['id' => 'sentUpdateModal']);
        $this->tester->click(['id' => 'notificationMail']);
        $this->tester->scrollTo(['id' => 'bulkChange']);
        $this->tester->wait(1);
        $this->tester->click(['id' => 'bulkChange']);
        $this->tester->waitForElementVisible(['id' => 'bulkChangeComplete']);

        return $this;
    }

    public function 取得_出荷伝票番号($rowNum)
    {
        return $this->tester->grabValueFrom("#search_result > tbody > tr:nth-child({$rowNum}) > td:nth-child(8) > div > input");
    }

    public function 取得_出荷日($rowNum)
    {
        return $this->tester->grabTextFrom("#search_result > tbody > tr:nth-child({$rowNum}) > td:nth-child(7)");
    }

    public function 取得_ステータス($rowNum)
    {
        return $this->tester->grabTextFrom("#search_result > tbody > tr:nth-child({$rowNum}) > td:nth-child(4) > span");
    }

    public function 件数変更($num)
    {
        $this->tester->selectOption('#form_bulk > div.row.justify-content-between.mb-2 > div.col-5.text-end > div:nth-child(1) > select', '/admin/order/page/1?page_count='.$num);

        return $this;
    }

    public function assertSortedStatusList($order)
    {
        $values = $this->tester->grabMultiple('.c-contentsArea__primaryCol tr > td:nth-child(4)');
        $expect = $values;
        usort($expect, function ($a, $b) {
            // order_status でソート
            $statusList = ['新規受付', '注文取消し', '対応中', '発送済み', '入金済み', '決済処理中', '購入処理中', '返品'];

            return array_search($a, $statusList) <=> array_search($b, $statusList);
        });

        if ($order === 'desc') {
            $expect = array_reverse($expect);
        }

        $this->tester->assertEquals($expect, $values);
    }

    public function assertSortedPriceList($order)
    {
        $values = array_map(function ($s) {
            // 一覧の購入金額の文字列から金額だけを抽出
            return (int) preg_replace('/(\n.*|\D)/', '', $s);
        }, $this->tester->grabMultiple('.c-contentsArea__primaryCol tr > td:nth-child(5)'));

        $expect = $values;
        if ($order === 'asc') {
            sort($expect);
        } else {
            rsort($expect);
        }

        $this->tester->assertEquals($expect, $values);
    }

    public function 遷移_メールを作成()
    {
        // メール履歴が多いと、フッターに隠れるため
        $this->tester->wait(1);
        $this->tester->scrollTo('#mailHistory > div > div > a');
        $this->tester->wait(1);
        $this->tester->seeElement('#mailHistory > div > div > a');
        $this->tester->click('#mailHistory > div > div > a');
        $this->tester->wait(1);
        $this->tester->click('#confirmFormChangeModal > div > div > div.modal-footer > a.btn.btn-ec-sub');

        return $this;
    }

    public function 選択_メールテンプレート($value)
    {
        $this->tester->selectOption('#template-change', $value);

        return $this;
    }

    public function メール送信()
    {
        $this->tester->click('#order-mail-form > div.c-conversionArea > div > div > div:nth-child(2) > div > div > button');
        $this->tester->click('#order-mail-form > div > div.c-conversionArea > div > div > div:nth-child(2) > div > div > button');

        return $this;
    }
}
