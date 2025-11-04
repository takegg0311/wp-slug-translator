# Coding Rules

## git管理

### コミットメッセージ
- コミットメッセージ1行目で、20文字程度の日本語で変更点を要約する
- コミットメッセージ1行目にはprefixを付ける
  - feat: 新機能の追加
  - fix: バグの修正
  - docs: ドキュメントのみの追加・更新
  - style: コードの意味に影響を与えないスタイル修正（空白、フォーマッティングなど）
  - refactor: feat(新機能の追加), fix(バグ修正) に該当しないコード修正
  - perf: パフォーマンス改善のためのコード修正
  - test: 新規テスト追加や既存テストコードの修正
  - chore: ビルドプロセスや外部ツール・ライブラリの修正
- 細かい変更点は2行目以降に箇条書きで列挙する

## 全般
- コーディングスタイルはGoogle Java Style Guideを参考にする
- 意味の単位でメソッドを分割する
- 意味のあるメソッドをクラスにまとめる
- 変数名には意味を与える
- メソッドのINPUT/OUTPUTは明確にする
- 外部依存は分離する（ `System.getProperty()` など）
- マジックナンバーは避ける
- 引数は早い段階でチェックする
- 標準出力に直接出力せず、Loggerを使用する
- 変数のスコープは最小単位に
- Utilityはfinal classに、privateコンストラクタを持つ
- Logger/Constants/Instance Variablesの順番で並べる
- ファイルパスを繋ぐときは言語の機能を使用する (javaの場合はFile.separator)

### ファイル名
- ファイル名は小文字で、スペースはアンダースコアで表す

### 変数名
- 変数名は小文字で、スペースはアンダースコアで表す
- Count を `cnt` とするような省略は避く

### 関数名
- 関数名は小文字で、スペースはアンダースコアで表す
- 動詞始まりにする

### 例外処理
- 例外のマルチキャッチは避ける
```
try {
    // 処理
} catch (Exception e) {
    // 例外処理
    String message = "failed to insert APITypes.";
    throw new LavisRepositoryRuntimeException(message, e);
}
```
### スコープ
- 変数定義は意味のある最小スコープで定義する
```
@Test
public void testInvalidContentType() {
    final String INVALID_CONTENT_TYPE = "\u0000";
    String content = buildTestContent(INVALID_CONTENT_TYPE);
}
```

### ログ
- ログレベルによって挙動が変わらないようにする
```
if (logger.isInfoEnabled()) {
    logger.info("testInvalidContentType: {}", INVALID_CONTENT_TYPE);
} else {
    logger.trace("testInvalidContentType: {}", INVALID_CONTENT_TYPE);
}
```

- 時間の掛かる処理以外に性能計測ログは付けない

### ハードコーディング
- 定数のコードへの埋め込みは避ける
```
capAlertString.getBytes(StandardCharsets.UTF_8)
```

- 絶対パスは使用しない

### 条件分岐

- if/elseが対等で無いケースの場合はガード節を使用する
```
if (特殊パターン) {
    throw Exception;
}
// 正常処理
```

- if文の{}は必ず使用する
```
if (条件) {
    // 処理
}
```

## Python固有のコーディングルール

- Google Python Style Guideを参考にする

### クラス名
- インターフェースはIで始める
- アブストクラスは末尾にBaseをつける
- 実装クラスは末尾にImplをつける

### 関数名
- 関数名は小文字で、スペースはアンダースコアで表す
- 動詞始まりにする
- スコープはメソッド名の先頭にアンダースコアを付けて示す
  - protectedメソッドは `_` で始める
  - privateメソッドは `__` で始める

### 型ヒント
- 型ヒントは必ず使用する
  - 引数: 関数の宣言部で、引数名の後ろに`: <型>`を付ける
  - 戻り値: 関数の宣言部で、戻り値の前に`-> <型>`を付ける
  - 変数: 変数の宣言部で、変数名の後ろに`: <型>`を付けるか、コメントで型を付ける
  - リスト: リストの型は `List[型]` で表す
```
// 関数の宣言部
def add(a: int, b: int) -> int:
    return a + b

// 変数の宣言部
a = 'Hello' # type: str

// リストの宣言部
from typing import List, Dict, Tuple
l = ['a', 'b', 'c'] # type: List[str]
d = {'a': 1, 'b': 2, 'c': 3} # type: Dict[str, int]
t = (True, False, ) # type: Tuple[bool, bool]
```
