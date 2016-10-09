INSERT INTO roles (name) VALUES
    ('課長')
    ,('PM')
    ,('開発員');

INSERT INTO item_meta_categories (name) VALUES
    ('一般')
    ,('タスク');

INSERT INTO item_categories (name, item_meta_category_id) VALUES
    ('議事項目', 1)
    ,('決定事項', 1)
    ,('次回会議', 1)
    ,('継続検討', 2)
    ,('顧客打ち合わせ', 2)
    ,('設計', 2)
    ,('要求分析', 2)
    ,('設計レビュー', 2)
    ,('プロジェクト計画', 2)
    ,('システムテスト仕様書', 2)
    ,('見積もり', 2)
    ,('デザインレビュー', 2)
    ,('コーディング', 2)
    ,('テストレビュー', 2)
    ,('コードレビュー', 2)
    ,('単体テスト', 2)
    ,('単体テスト仕様書', 2)
    ,('総合テスト', 2)
    ,('総合テスト仕様書', 2)
    ,('システムテスト', 2)
    ,('検収', 2)
    ,('不具合関連', 2)
    ,('工程', 2);