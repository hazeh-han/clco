# WP Photobook Flipbook

유료 플러그인 없이, ACF 갤러리 필드에 올린 사진을 실제 책처럼 페이지를 넘겨서 보여주는 워드프레스 숏코드 플러그인입니다. 외부 JS 라이브러리 의존 없이 순수 CSS 3D transform으로 동작해서 가볍게 운용할 수 있어요.

## 요구 사항

- 워드프레스
- **ACF (Advanced Custom Fields)** — 무료 버전으로 충분합니다.

## 설치

1. `wp-photobook-flipbook` 폴더를 사이트의 `wp-content/plugins/`에 업로드합니다.
2. 워드프레스 관리자 → 플러그인 메뉴에서 **WP Photobook Flipbook**을 활성화합니다.

## 사용법

### 1. ACF 갤러리 필드 만들기

ACF → 필드 그룹에서 **Gallery** 타입 필드를 하나 추가합니다. 필드명(Field Name)은 기본값인 `photo_gallery`를 쓰거나 원하는 이름으로 바꿔도 됩니다. 이 필드 그룹을 사진을 넣을 페이지(또는 포스트 타입)에 연결하세요.

### 2. 사진 업로드

해당 페이지 편집 화면에서 갤러리 필드에 사진 80장을 원하는 순서로 업로드합니다.

### 3. 숏코드 삽입

같은 페이지 본문(또는 커스텀 HTML 블록)에 숏코드를 추가합니다.

```
[wp_photobook]
```

필드명을 바꿨다면:

```
[wp_photobook field="my_gallery_field"]
```

## 숏코드 옵션

| 속성              | 기본값              | 설명                                                         |
| ----------------- | -------------------- | ------------------------------------------------------------ |
| `field`           | `photo_gallery`      | ACF 갤러리 필드명                                             |
| `post_id`         | 현재 페이지          | 다른 페이지의 갤러리를 불러오고 싶을 때 사용                 |
| `size`            | `large`              | 사용할 워드프레스 이미지 사이즈 (`medium`, `large`, `full` 등) |
| `cover`           | `yes`                | 표지/뒤표지를 자동 생성할지 여부 (`no`로 끄면 사진만 바로 시작) |
| `cover_title`     | 사이트 제목          | 표지에 표시할 제목                                            |
| `cover_subtitle`  | `{장수} PHOTOGRAPHS` | 표지에 표시할 부제                                            |

예시:

```
[wp_photobook field="wedding_photos" size="large" cover_title="우리 결혼식" cover_subtitle="2026.05.10"]
```

## 성능 관련 메모

- 처음에는 현재 스프레드에 필요한 이미지 2장만 불러오고, 다음/이전 스프레드 이미지만 미리 한 장씩 더 준비해둡니다(전체 80장을 한 번에 불러오지 않음).
- `size` 속성을 `full`이 아니라 `large`나 `medium_large` 정도로 지정하면 워드프레스가 자동 생성한 리사이즈본을 쓰게 되어 로딩이 훨씬 가벼워집니다.
- 화면이 좁아지면(모바일) 자동으로 한 번에 한 장씩 보는 모드로 전환됩니다.

## 커스터마이징

디자인(색상, 폰트, 여백)은 `wp-photobook-flipbook.php`의 `wp_photobook_styles()` 함수 안 CSS에서 `--wppb-*` 커스텀 프로퍼티를 바꿔서 조정할 수 있습니다. 라이트/다크 모드 색상이 모두 정의되어 있습니다.
