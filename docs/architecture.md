# Project Knowledge Graph

```mermaid
erDiagram
    User {
        bigint id PK
        string name
        string nickname
        string mobile UK
        string national_code UK
        date birth_date
        string iban
        string avatar
        bigint province_id FK
        bigint city_id FK
        boolean show_phone_publicly
        string role
        string email
        timestamp email_verified_at
        string password
        boolean is_verified
    }

    Bank {
        bigint id PK
        string name
        string slug UK
        string logo_path
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    BankPlan {
        bigint id PK
        bigint bank_id FK
        string title
        decimal interest_rate
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    Location {
        bigint id PK
        bigint parent_id FK
        string name
        string slug
        timestamp created_at
        timestamp updated_at
    }

    Advertisement {
        bigint id PK
        bigint user_id FK
        bigint bank_id FK
        string type
        string title
        bigint loan_amount
        bigint transfer_price
        decimal interest_rate
        bigint bank_plan_id FK
        bigint location_id FK
        string description
        string status
        timestamp created_at
        timestamp updated_at
    }

    User ||--o{ Advertisement : publishes
    User }o--|| Location : province
    User }o--|| Location : city
    Bank ||--o{ Advertisement : contains
    Bank ||--o{ BankPlan : offers
    BankPlan ||--o{ Advertisement : describes
    Location ||--o{ Location : contains
    Location ||--o{ Advertisement : hosts

    User ||--o{ PersonalAccessToken : owns
```

`User.is_verified` مشخص می‌کند کاربر اجازهٔ ثبت آگهی دارد یا نه و مقدار پیش‌فرض آن `false` است.

`Advertisement` دیگر ستون‌های متنی `plan` و `city` ندارد و مستقیماً به `BankPlan` و شهرِ `Location` متصل است. هر شهر با `parent_id` به استان خود وصل می‌شود و استان‌ها با `parent_id IS NULL` مشخص می‌شوند.

## Authentication API

```mermaid
flowchart LR
    Client[Vue AuthModal] -->|POST /api/auth/send-otp| Send[AuthController.sendOtp]
    Send -->|120 seconds| OTP[(Database otps)]
    Client -->|POST /api/auth/verify-otp| Verify[AuthController.verifyOtp]
    Verify --> OTP
    Verify --> Existing{User exists?}
    Existing -->|yes| User[(User)]
    Existing -->|yes| Token[(Sanctum PersonalAccessToken)]
    Existing -->|no| Details[Registration details]
    Details --> Complete[AuthController.completeRegistration]
    Complete --> User
    Complete --> Token
    Client -->|POST /api/auth/logout| Logout[AuthController.logout]
    Logout -->|revoke current token| Token
    Send -.->|1 request / 2 minutes per IP + mobile| Limiter[OTP RateLimiter]
```

The passwordless flow normalizes Persian and Arabic digits before validating Iranian mobile numbers with `^09[0-9]{9}$`, stores a random five-digit OTP in MySQL for 120 seconds, and branches after OTP validation: existing users receive a Sanctum personal access token immediately, while new users must submit name and optional email through `POST /api/auth/complete-registration` before their account and token are created. For local and testing environments only, the generated OTP is included in the structured creation log to support manual testing; production logs never contain the raw OTP.

## Marketplace API

```mermaid
flowchart LR
    Client[Vue HomeView] --> Ads[GET /api/advertisements]
    Client --> Detail[GET /api/advertisements/{id}]
    Client --> Banks[GET /api/banks]
    Ads --> Filter[Validated filters]
    Filter --> Approved[approved advertisements + active banks]
    Approved --> Resource[AdvertisementResource]
    Resource --> Client
    Detail --> Resource
    Banks --> Client
    Client[Vue HomeView] --> Locations[GET /api/locations/provinces]
    Client --> Plans[GET /api/banks/{id}/plans]
    Locations -->|province with children| Client
    Plans -->|active bank plans| Client
```

`GET /api/user/ads` با احراز هویت Sanctum فقط آگهی‌های متعلق به کاربر جاری را برمی‌گرداند. `BaseDashboardLayout` ظاهر حساب‌محور با پس‌زمینه `#F9FAFB`، سایدبار سفید، کارت پروفایل و وضعیت احراز هویت را فراهم می‌کند و `UserDashboardView` تب‌های وضعیت، کارت‌های مدیریت آگهی، empty state و کنترل‌های واکنش‌گرا را ارائه می‌دهد.

صفحه `AdDetailView` در مسیر `/advertisements/:id` جزئیات کامل یک آگهی تاییدشده را از `AdvertisementController.show` دریافت می‌کند. این صفحه breadcrumb، مشخصات کلیدی، توضیحات، هشدار معامله امن، اشتراک‌گذاری، نشان‌کردن، گزارش و CTA اطلاعات تماس دارد؛ اطلاعات تماس فقط پس از ورود کاربر نمایش داده می‌شود و کاربر مهمان به `AuthModal` هدایت می‌شود.

`GET /api/advertisements` فیلترهای `type`, `bank_id`, `bank_plan_id`, `location_id`, `min_amount`, `max_amount`, `search` و بازه قیمت را می‌پذیرد و با `paginate(12)` فقط آگهی‌های تاییدشده از بانک‌های فعال را برمی‌گرداند. پارامتر `sort` نیز با مقدار پیش‌فرض `latest` پشتیبانی می‌شود: `latest` (تاریخ نزولی)، `amount_desc` (مبلغ نزولی)، `amount_asc` (مبلغ صعودی)، `price_asc` (قیمت واگذاری صعودی)، `price_desc` (قیمت واگذاری نزولی) و `rate_asc` (سود/کارمزد صعودی). مقدار نامعتبر به `latest` fallback می‌شود. کنترل `SortControls` در دسکتاپ به‌صورت `SelectButton` و در موبایل به‌صورت `Select` نمایش داده می‌شود و مقدار انتخاب‌شده را در `?sort=` نگه می‌دارد. `GET /api/banks` فقط بانک‌های فعال را برای فیلترهای صفحه اصلی ارائه می‌کند. داده‌های نمونه توسط `MarketplaceSeeder` پس از نقش‌ها و کاربران نمونه درج می‌شوند.

## Advertisement creation access flow

```mermaid
flowchart TD
    Click[HomeView.handleNewAdClick] --> AuthState{auth token موجود است؟}
    AuthState -->|خیر| Login[AuthModal: ورود با OTP]
    AuthState -->|بله| Verified{User.is_verified؟}
    Verified -->|خیر| Restricted[PrimeVue Dialog: تکمیل احراز هویت]
    Restricted --> Dashboard[پنل کاربری / مسیر احراز هویت]
    Verified -->|بله| Create[CreateAdModal]
    Create --> Store[POST /api/advertisements]
    Store --> Sanctum[auth:sanctum]
    Sanctum --> Verify[verified.user]
    Verify --> Pending[Advertisement.status = pending]
```

روت `POST /api/advertisements` با `auth:sanctum` و `verified.user` محافظت می‌شود. مهمان پاسخ ۴۰۱ می‌گیرد، کاربر واردشدهٔ بدون احراز پاسخ ۴۰۳ با کد `USER_UNVERIFIED` دریافت می‌کند و فقط کاربر معتبر می‌تواند آگهی را با وضعیت `pending` ایجاد کند. `apiClient` این پاسخ ۴۰۳ را به رویداد `auth:unverified` تبدیل می‌کند تا رابط کاربری دیالوگ راهنما را نمایش دهد.

## Database OTP architecture

```mermaid
flowchart LR
    Client[Vue AuthModal] --> Send[AuthController.sendOtp]
    Send --> Service[OtpService.issue]
    Service --> Rate[DB count: otps.created_at in last 2 minutes]
    Service --> Invalidate[Consume previous valid OTPs]
    Service --> Otp[(otps)]
    Client --> Verify[AuthController.verifyOtp]
    Verify --> Check[OtpService.validate for new users]
    Verify --> Consume[OtpService.consume for existing users]
    Complete --> ConsumeRegistration[OtpService.consume for registration]
    Consume --> Lock[DB lockForUpdate]
    ConsumeRegistration --> Lock
    Lock --> Otp
    Otp -->|consumed_at set| Token[Sanctum token]
```

OTP هرگز در Cache لاراول ذخیره نمی‌شود. مدل `Otp` دارای `mobile`, `code`, `expires_at`, `consumed_at`, `ip_address` و `user_agent` است و ایندکس ترکیبی `mobile/consumed_at/expires_at` دارد. `OtpService` rate limit را با دیتابیس انجام می‌دهد، OTPهای قبلی معتبر را با `consumed_at` باطل می‌کند و verify موفق را در تراکنش با `lockForUpdate` مصرف می‌کند تا Replay هم‌زمان ممکن نباشد. لاگ‌های صدور، ابطال و اعتبارسنجی فقط شناسه OTP و شماره موبایل را ثبت می‌کنند و متن کد را ثبت نمی‌کنند.

## RBAC

```mermaid
flowchart LR
    OTP[AuthController.verifyOtp] -->|first login| Assign[syncRoles: buyer + seller]
    Assign --> User[User with Spatie HasRoles]
    User --> Buyer[buyer: create/search/request/offer]
    User --> Seller[seller: create/manage own ads]
    User --> Admin[super-admin/admin/operator]
    Admin --> AdminAPI[/api/admin/*]
    Operator[operator] --> OperatorAPI[/api/operator/*]
    AdminAPI --> Audit[LogRoleAccess]
    OperatorAPI --> Audit
    Audit --> Spatie[Spatie role middleware]
```

نقش‌ها و مجوزها در `RolesAndPermissionsSeeder` تعریف می‌شوند. `User` از trait رسمی `HasRoles` استفاده می‌کند و با `syncRoles` چند نقش هم‌زمان می‌گیرد. روت‌های admin و operator با `auth:sanctum`، middleware audit و middleware رسمی `role` محافظت می‌شوند و کاربر فاقد نقش پاسخ ۴۰۳ دریافت می‌کند. guard فرانت با فرمت `[AuthGuard:checkRole]` مسیر پنل کاربر یا ادمین را تعیین می‌کند؛ این guard جایگزین مجوز backend نیست.

## Frontend Index

صفحه اصلی در `resources/js/components/HomeView.vue` با Vue 3 و Composition API ساخته شده است و از `AdCard.vue` برای نمایش آگهی‌ها استفاده می‌کند. هر دو کامپوننت داخل Blade با mount point به نام `#app` رندر می‌شوند و کل رابط کاربری راست‌چین و واکنش‌گرا است. صفحه از الگوی مینیمال دیوار استفاده می‌کند: هدر چسبان، فیلترهای متنی در ستون راست، و گرید کارت‌های آگهی در فضای باقی‌مانده. بنرهای حجیم و وابستگی PrimeVue حذف شده‌اند و کنترل‌های فرم به HTML بومی منتقل شده‌اند.

### Frontend setup

پکیج `vue` و `primeicons` در پروژه نصب شده‌اند. در `resources/js/app.js`، Vue با آیکون‌های سبک PrimeIcons mount می‌شود و برای کامپایل فایل‌های `.vue` نیز `@vitejs/plugin-vue` در `vite.config.js` فعال است. فونت اصلی ایران‌سنس به‌صورت محلی در `resources/fonts/IRANSans-Regular.woff2`، `IRANSans-Medium.woff2` و `IRANSans-Bold.woff2` قرار دارد و در `resources/css/app.css` با `@font-face` برای کل پروژه اعمال شده است.

فهرست‌های HomeView از `GET /api/advertisements` و `GET /api/banks` دریافت می‌شوند و دیگر داده‌ی mock در کامپوننت وجود ندارد. لایه شبکه در مسیر دقیق `src/resources/js/services` قرار دارد: `apiClient.js` کلاینت Axios و interceptorهای احراز هویت را فراهم می‌کند، `adService.js` عملیات آگهی، `bankService.js` عملیات بانک و `authService.js` جریان OTP و session را مدیریت می‌کنند؛ `index.js` export مرکزی این سرویس‌ها است. تست‌های کامپوننتی و سرویس‌ها با دستور `npm run test` اجرا می‌شوند.

## Dashboard architecture

داشبوردها با Vue Router، Pinia و الگوی layout مشترک Sakai پیاده‌سازی شده‌اند. `BaseDashboardLayout.vue` مسئول قاب RTL، هدر و کانتینر اصلی است و `AppMenu.vue` فقط config منوی دریافت‌شده را render می‌کند؛ بنابراین منوی کاربر و ادمین هیچ state یا گزینه مشترک ناخواسته‌ای ندارند.

### Nested user dashboard routes

```mermaid
flowchart TD
    UserPanel[/user] --> MyAds[MyAdsView]
    UserPanel --> Bookmarks[BookmarksView]
    UserPanel --> Profile[ProfileView]
    UserPanel --> History[HistoryView]
    UserPanel --> Verification[VerificationView]
    UserPanel --> Settings[SettingsView]
    MyAds --> LoadMyAds[adService.getMyAds(status)]
    Bookmarks --> LoadBookmarks[adService.getBookmarks()]
    Profile --> UpdateProfile[authService.updateProfile()]
    Verification --> SubmitKyc[authService.submitKyc(formData)]
    History --> LocalStorage[recent_ads]
    Settings --> SavePreferences[local state + logger]
```

مسیرهای تودرتو پنل کاربری در `resources/js/router/index.js` با نام‌های `user.my-ads`, `user.bookmarks`, `user.profile`, `user.history`, `user.verification` و `user.settings` تعریف شده‌اند. کل محتوای اصلی از `RouterView` داخل `BaseDashboardLayout` رندر می‌شود و گزینه‌های سایدبار از `resources/js/config/menus.js` به‌صورت ماژولار و مستقل از منوی ادمین مدیریت می‌شوند. همچنین خروج از حساب با دیالوگ تأیید در `BaseDashboardLayout` و فراخوانی `authService.logout()` انجام می‌شود.

### User dashboard module integrations

- `MyAdsView` تب‌های `همه / منتشر شده / در انتظار بررسی / رد شده / منقضی` را حفظ می‌کند و از `adService.getMyAds(status)` برای فیلترها استفاده می‌کند.
- `CreateAdModal` با `bankService.getAll()` و `bankService.getPlans(bankId)` داده‌های بانک و طرح را می‌گیرد و پس از ساخت فرم، با `adService.create(payload)` ارسال می‌کند.
- `VerificationView` فرم KYC را با `authService.submitKyc(formData)` ارسال می‌کند و وضعیت را در UI نشان می‌دهد.
- `ProfileView` و `SettingsView` به‌صورت ماژولار روی state‌های Pinia و فرم‌های محلی اجرا می‌شوند.
- `HistoryView` شناسه آگهی‌های اخیر را در `localStorage` با کلید `recent_ads` نگه می‌دارد.

### Bookmark architecture

بوک‌مارک‌ها فقط در جدول واسط `bookmarks` با ستون‌های `user_id` و `advertisement_id` نگه‌داری می‌شوند و روی این دو ستون یک unique constraint وجود دارد. رابطه `User::bookmarks()` رکوردهای pivot متعلق به همان کاربر را برمی‌گرداند و `Bookmark::advertisement()` آگهی مرتبط را resolve می‌کند؛ `Advertisement::bookmarkedBy()` رابطه معکوس است.

```mermaid
flowchart LR
    Detail[AdDetailView] --> Toggle[POST /api/user/bookmarks/{advertisementId}]
    Toggle --> BookmarkController[BookmarkController.toggle]
    BookmarkController --> Pivot[(bookmarks: user_id + advertisement_id)]
    Panel[BookmarksView] --> Fetch[GET /api/user/bookmarks]
    Fetch --> BookmarkIndex[BookmarkController.index]
    BookmarkIndex --> UserRelation[request.user.bookmarks]
    UserRelation --> Pivot
    Panel --> Remove[DELETE /api/user/bookmarks/{advertisementId}]
    Remove --> BookmarkDelete[BookmarkController.destroy]
    BookmarkDelete --> Pivot
```

هر سه endpoint زیر `auth:sanctum` هستند. `GET /api/user/bookmarks` با relation کاربر جاری و eager loading روابط `advertisement.bank`, `advertisement.bankPlan`, `advertisement.location` و `advertisement.user` اجرا می‌شود و هرگز از `Advertisement::all()` استفاده نمی‌کند. toggle آگهی approved را برای همان کاربر ایجاد یا حذف می‌کند و پاسخ `bookmarked` را برمی‌گرداند؛ حذف نیز فقط رکورد pivot همان کاربر را حذف می‌کند. `BookmarksView` مقدار اولیه `bookmarks` را آرایه خالی می‌گذارد و فقط پاسخ واقعی API را پس از فیلتر شناسه‌دار نمایش می‌دهد؛ وضعیت خالی شامل پیام «هنوز هیچ آگهی را نشان نکرده‌اید» و بازگشت به صفحه آگهی‌هاست. لاگ‌های ساختاری fetch با تگ `[Bookmarks:fetch]` در هر دو لایه ثبت می‌شوند و تست جداسازی مالکیت در `tests/Feature/BookmarkTest.php` قرار دارد.

## Profile API and user data

آپلود آواتار از مسیر `POST /api/user/profile/avatar` با middleware `auth:sanctum` انجام می‌شود. ورودی multipart باید فیلد `avatar` با نوع تصویر JPG/JPEG/PNG/WEBP و حداکثر ۲MB باشد. فایل در دیسک `public` و مسیر `avatars/` ذخیره می‌شود، آواتار قبلی پیش از ذخیرهٔ فایل جدید حذف می‌شود و پاسخ شامل پیام فارسی، `avatar_url` معتبر و آبجکت تازهٔ `user` است. لینک `public/storage` با `php artisan storage:link` به `storage/app/public` متصل است. `ProfileView` پس از پاسخ موفق، هم state محلی و هم `useAuthStore.user` و localStorage را همان لحظه به‌روزرسانی می‌کند و خطا/موفقیت را با PrimeVue Toast نشان می‌دهد.
برای سرو قطعی تصویر، `GET /api/user/profile/avatar` نیز با `auth:sanctum` فایل آواتار کاربر جاری را از دیسک public stream می‌کند. `ProfileView` این endpoint را با Axios و `responseType: blob` می‌خواند و object URL می‌سازد؛ بنابراین حتی وقتی web server مسیر junction `public/storage` را به fallback HTML resolve کند، تصویر با Bearer token و MIME واقعی نمایش داده می‌شود.

## Full KYC and operator review

```mermaid
erDiagram
    User ||--o{ UserVerification : submits

    UserVerification {
        bigint id PK
        bigint user_id FK UK
        string home_phone
        text postal_address
        string residence_document_path
        string national_code
        string national_card_serial
        string national_card_front_path
        string national_card_back_path
        string birth_certificate_p1_path
        string birth_certificate_p2_path
        string job_document_path
        string iban
        boolean ownership_confirmed
        enum status "pending|approved|rejected"
        bigint reviewed_by FK
        timestamp reviewed_at
        text rejection_reason
    }
```

کاربر از `GET /api/user/kyc/status` آخرین پرونده و از `GET /api/user/kyc/history` تمام درخواست‌های خود را به ترتیب نزولی می‌خواند. مدارک با `POST /api/user/kyc/submit` به‌صورت `multipart/form-data` ارسال می‌شوند. اگر رابطهٔ `UserVerification` وجود نداشته باشد، پاسخ دقیقاً `status: not_submitted` و `data: null` دارد و فرم کامل بدون قفل نمایش داده می‌شود. جدول تاریخچه شامل شماره درخواست، تاریخ ثبت شمسی، وضعیت، علت رد و تاریخ بررسی است.

علت خطای ۵۰۰ ثبت‌شده در لاگ، تلاش submit برای به‌روزرسانی `users.national_code` با کدی بود که قبلاً متعلق به کاربر دیگری بود و به `users_national_code_unique` برخورد می‌کرد. `SubmitUserVerificationRequest` اکنون یکتایی کد ملی را با نادیده گرفتن کاربر جاری اعتبارسنجی می‌کند و چنین خطایی را قبل از تراکنش با پاسخ ۴۲۲ فارسی برمی‌گرداند. سرویس قبل از ذخیره پوشهٔ خصوصی را می‌سازد و هر مسیر فایل را فقط پس از `hasFile` ذخیره می‌کند؛ خطاهای پیش‌بینی‌نشدهٔ submit نیز با تگ `[KYC:SubmitFailed]` و بدون اطلاعات حساس لاگ می‌شوند. در فرانت‌اند، Axios برای `FormData` هدر `Content-Type` را دستی قفل نمی‌کند تا boundary صحیح را خودش بسازد و تعهدنامه با مقدار `1` ارسال می‌شود.

رابطهٔ `User` با `UserVerification` اکنون یک‌به‌چند است و unique قبلی `user_id` با migration چنددرخواستی حذف شده است. قانون چرخهٔ عمر این است: پروندهٔ `approved` یا هر `pending` فعال، ارسال جدید را با خطای ۴۲۲ مسدود می‌کند؛ فقط وقتی آخرین پرونده `rejected` باشد، submit یک رکورد جدید با وضعیت `pending` می‌سازد. `VerificationView` تاریخچه را با PrimeVue DataTable نمایش می‌دهد و عملیات «اصلاح و ارسال مجدد» را فقط برای آخرین ردشده فعال می‌کند.

`User.is_verified` پرچم دسترسی عملیاتی و همتای sync‌شدهٔ وضعیت `UserVerification.status=approved` است. کنترلر review هنگام تایید یا رد، هر دو رکورد را در یک تراکنش به‌روزرسانی می‌کند؛ middleware `EnsureUserIsVerified` مقدار تازهٔ `is_verified` را می‌خواند و با تگ `[Gate:CheckVerification]` نتیجهٔ گیت را لاگ می‌کند. برای جلوگیری از باقی‌ماندن snapshot قدیمی login، router guard پیش از مسیرهای احراز‌شده پروفایل کاربر را از API refresh و در Pinia/localStorage ذخیره می‌کند.

اپراتور یا ادمین دارای مجوز `review-kyc`، صف را از `GET /api/admin/verifications?status=pending` می‌خواند، جزئیات را از `GET /api/admin/verifications/{id}` دریافت می‌کند و مدارک را فقط از مسیر دانلود امن همان API مشاهده می‌کند. نتیجه با `PATCH /api/admin/verifications/{id}/review` ثبت می‌شود: تایید، `User.is_verified` را `true` می‌کند و رد، دلیل رد را ذخیره و دسترسی تاییدشده را غیرفعال نگه می‌دارد.

```mermaid
stateDiagram-v2
    [*] --> not_submitted : no verification record
    not_submitted --> pending : user submits documents
    pending --> approved : operator/admin approves
    pending --> rejected : operator/admin rejects + reason
    rejected --> pending : user edits and resubmits
    approved --> [*]
```

`VerificationView` تا دریافت پاسخ API در حالت loading است؛ مقدار اولیهٔ status هرگز `pending` نیست. وضعیت `pending` فرم را قفل و پیام صف بررسی را همراه تاریخ ارسال نشان می‌دهد، `approved` کارت سبز تایید را نشان می‌دهد و `rejected` دلیل رد و دکمهٔ ویرایش و ارسال مجدد را نمایش می‌دهد. ارسال مجدد، داده‌های متنی قبلی را در فرم نگه می‌دارد و پس از اصلاح مدارک وضعیت را دوباره `pending` می‌کند.

در بازطراحی UI، محتوای `VerificationView` داخل کانتینر محدود و responsive با فاصلهٔ عمودی یکنواخت قرار دارد. بنر pending به کارت کهربایی ساختاریافته با آیکون ساعت، توضیح فرایند و بج تاریخ ارسال تبدیل شده و تاریخچه داخل کارت سفید با PrimeVue DataTable، هدرهای خاکستری ملایم، Tagهای وضعیت و اسکرول افقی موبایل ارائه می‌شود. لاگ `[VerificationView:renderHistory]` تعداد رکوردها و وضعیت آخرین پرونده را هنگام render تاریخچه ثبت می‌کند.

`TestUserAdsSeeder` برای کاربر ۸، ۳۰ آگهی تستی با وضعیت‌های `published=10`، `pending_approval=8`، `rejected=6`، `handed_over=4` و `expired=2` می‌سازد و FKهای بانک، طرح و شهر را از دیتابیس انتخاب می‌کند. `MyAdsView` تب‌های ردشده و منقضی را جداگانه نمایش می‌دهد تا شمارندهٔ ردشده دقیقاً ۶ و شمارندهٔ منقضی ۲ باشد؛ تب همه نیز هر ۳۰ رکورد را نشان می‌دهد.

در ستون عملیات، پرونده‌های `pending` و `approved` دکمهٔ «مشاهده مدارک» دارند که Dialog فقط‌خواندنی جزئیات متنی و thumbnail مدارک را باز می‌کند؛ تصاویر بزرگ با کلیک روی thumbnail نمایش داده می‌شوند. پروندهٔ `rejected` فقط دکمهٔ «اصلاح و ارسال مجدد» دارد و متن خام `---` در عملیات استفاده نمی‌شود. جزئیات از `GET /api/user/kyc/{id}/details` و فایل‌ها از `GET /api/user/kyc/{id}/documents/{type}` دریافت می‌شوند؛ هر دو endpoint مالکیت رکورد را با relation کاربر جاری کنترل می‌کنند و فایل‌ها از دیسک خصوصی stream می‌شوند.

Dialog جزئیات با `modal="true"` و mask نیمه‌شفاف تیره/blur شده render می‌شود؛ root، header، content و footer به‌صورت explicit سفید و مات هستند، content اسکرول داخلی با سقف ۸۵٪ viewport دارد و عرض Dialog در موبایل به ۹۰٪ viewport محدود می‌شود. این تنظیمات از نفوذ بصری محتوای صفحهٔ زیرین به مودال جلوگیری می‌کنند و دکمهٔ close داخلی و footer هر دو Dialog را می‌بندند.

`VerificationAccessDialog` نیز با حذف عنوان header پیش‌فرض و انتقال عنوان به جریان عمودی محتوای اصلی بازطراحی شده است. Dialog عرض `90vw` با سقف ۴۴۰ پیکسل دارد، محتوای آن پدینگ ۲۴ پیکسل و فاصلهٔ استاندارد بین آیکون، عنوان، توضیح و CTA دارد؛ دکمهٔ close در RTL با فاصلهٔ ۱۶ پیکسل از گوشهٔ چپ قرار می‌گیرد و سطح content به‌صورت global سفید و مات نگه داشته می‌شود تا resetهای CSS باعث فشردگی یا شفافیت نشوند.

## Advertisement lifecycle management

### Lifecycle data model

```mermaid
erDiagram
    User ||--o{ Advertisement : owns
    Bank ||--o{ BankPlan : offers
    Bank ||--o{ Advertisement : contains
    BankPlan ||--o{ Advertisement : describes
    Location ||--o{ Advertisement : hosts

    Advertisement {
        bigint id PK
        bigint user_id FK
        bigint bank_id FK
        bigint bank_plan_id FK
        bigint location_id FK
        enum type "supply|demand"
        string title
        text description
        bigint loan_amount
        bigint assignment_price
        decimal profit_rate
        unsigned installment_count
        enum status "pending_approval|published|rejected|expired|handed_over"
        text rejection_reason
        bigint views_count
        timestamp deleted_at
        timestamp created_at
        timestamp updated_at
    }
```

`advertisements` از Soft Delete استفاده می‌کند. مقدارهای legacy (`pending`, `approved`, `closed` و فیلدهای `transfer_price`/`interest_rate`) فقط برای سازگاری migration و کلاینت‌های قدیمی نگه داشته شده‌اند؛ قرارداد جدید از `assignment_price`, `profit_rate` و وضعیت‌های استاندارد lifecycle استفاده می‌کند.

### Status transitions

```mermaid
stateDiagram-v2
    [*] --> pending_approval : verified user creates
    pending_approval --> published : admin approves
    pending_approval --> rejected : admin rejects + reason
    rejected --> pending_approval : owner edits and resubmits
    published --> handed_over : owner marks transferred
    published --> expired : lifecycle expiry job
    pending_approval --> [*] : owner soft deletes
    published --> [*] : owner soft deletes
    rejected --> [*] : owner soft deletes
```

### REST API and authorization

```mermaid
flowchart LR
    Wizard[AdWizard / CreateAdModal] -->|POST /api/advertisements| Store[AdvertisementController.store]
    Store --> Verify[auth:sanctum + verified.user]
    Verify --> Pending[pending_approval]
    Panel[MyAdsView] -->|GET /api/user/advertisements?status=...| Mine[AdvertisementController.userAds]
    Panel -->|PUT /api/user/advertisements/{id}| Update[AdvertisementController.update]
    Panel -->|PATCH /api/user/advertisements/{id}/status| Transfer[changeStatus]
    Panel -->|DELETE /api/user/advertisements/{id}| Delete[destroy + SoftDeletes]
    Update --> Policy[AdvertisementPolicy ownership check]
    Transfer --> Policy
    Delete --> Policy
    Update --> Pending
    Transfer --> HandedOver[handed_over]
```

The create and update payloads are validated by `StoreAdvertisementRequest`: bank plans must belong to the selected bank, locations must be cities, amounts are positive, titles are at least 10 characters and cannot contain phone numbers, and financial/installment values are bounded. `AdvertisementPolicy` restricts update, status change, and deletion to the owner. Every controller action writes structured context logs.

### Panel components

- `AdWizard.vue`: three-step bank, financial, and location/content workflow; loads bank plans when the bank changes, formats money inputs, and supports create/edit modes.
- `CreateAdModal.vue`: modal shell for the home page creation flow; closes after success and emits the created advertisement.
- `MyAdsView.vue`: status tabs, lifecycle badges, rejection reason, edit wizard, handed-over action, and delete confirmation dialog.
- `adService.js` and `useUserAds.js`: centralized REST calls and Pinia loading/state management with structured frontend logging.

## Error handling and localization

## Global verification access gate

```mermaid
flowchart TD
        Request[Authenticated API request] --> Auth[auth:sanctum]
        Auth --> Gate{is_verified?}
        Gate -->|true| Continue[Protected controller action]
        Gate -->|false| Allowed{Profile, KYC, or logout?}
        Allowed -->|yes| Continue
        Allowed -->|no| Denied[403 KYC_REQUIRED]
        Denied --> Interceptor[apiClient response interceptor]
        Interceptor --> Dialog[Global VerificationAccessDialog]
        Dialog --> KYC[/user/verification]
```

The canonical denial response is:

```json
{
    "message": "شما به این بخش دسترسی ندارید. لطفاً ابتدا فرایند احراز هویت خود را تکمیل کنید.",
    "code": "KYC_REQUIRED"
}
```

### Access matrix

| Capability | Unverified user | Verified user | Enforcement |
| --- | --- | --- | --- |
| Public advertisement list/detail | Allowed | Allowed | Public routes |
| `GET/PUT /api/user/profile` | Allowed | Allowed | `auth:sanctum` |
| `GET /api/user/kyc/status` | Allowed | Allowed | `auth:sanctum` |
| `POST /api/user/kyc/submit` | Allowed | Allowed | `auth:sanctum` |
| `POST /api/auth/logout` | Allowed | Allowed | `auth:sanctum` |
| Create advertisement | Denied | Allowed | `verified.user` |
| Edit/delete/manage own advertisements | Denied | Allowed | `verified.user` + `AdvertisementPolicy` |
| Contact advertiser | Denied | Allowed | `verified.user` |
| Bookmark list/add/remove | Denied | Allowed | `verified.user` |
| My ads, bookmarks, history, settings routes | Denied | Allowed | Vue Router `requiresVerification` |

`EnsureUserIsVerified` is applied at the API boundary, while `router.beforeEach`, `apiClient`, and `VerificationAccessDialog.vue` provide immediate frontend feedback and a one-click route to KYC. The gate reads the current `is_verified` value from the authenticated user; once it becomes true, no cached denial state is retained.

زبان پیش‌فرض Laravel در `config/app.php` روی `fa` و جهت رابط روی `rtl` تنظیم شده است. ترجمه‌های استاندارد اعتبارسنجی و نام فارسی فیلدها در `lang/fa/validation.php` نگه‌داری می‌شوند؛ بنابراین خطاهای `FormRequest` و `Request::validate()` متن خام یا انگلیسی به کاربر برنمی‌گردانند.

```mermaid
flowchart LR
    Request[API Request] --> Laravel[Laravel Validation]
    Laravel --> Validation[ValidationException]
    Validation --> Persian[lang/fa/validation.php]
    Persian --> JSON422[پیام کلی + errors فارسی]
    Exception[Authentication / Authorization / NotFound / Server] --> Handler[bootstrap/app.php withExceptions]
    Handler --> JSON[پاسخ JSON فارسی با کد HTTP]
    JSON --> Axios[apiClient response interceptor]
    Axios --> Map[نگاشت کد خطا به پیام فارسی]
    Map --> Bus[resources/js/utils/toastBus.js]
    Bus --> PrimeVue[PrimeVue Toast سراسری]
```

`bootstrap/app.php` برای خطاهای اعتبارسنجی، احراز هویت، دسترسی و نبود رکورد پاسخ‌های JSON فارسی تولید می‌کند. خطاهای ۵۰۰ به‌صورت کامل با context درخواست در لاگ Laravel ثبت می‌شوند، اما جزئیات exception هرگز به کاربر نمایش داده نمی‌شود و فقط پیام عمومی امن برمی‌گردد.

`resources/js/services/apiClient.js` خطاهای شبکه و وضعیت‌های ۴۰۱، ۴۰۳، ۴۲۲، ۴۲۹ و ۵۰۰ به بالا را به پیام‌های فارسی کنترل‌شده تبدیل می‌کند. پیام ۴۲۲ از اولین مقدار آرایه `errors` استخراج می‌شود و هیچ متن خامی از exception یا پاسخ سرور مستقیماً در رابط کاربری قرار نمی‌گیرد. `toastBus.js` با رویداد `app:toast` interceptor را به Toast سراسری PrimeVue در `app.js` متصل می‌کند.

```mermaid
flowchart LR
    ProfileView[ProfileView] --> Show[GET /api/auth/profile]
    ProfileView --> Update[PUT /api/auth/profile]
    ProfileView --> Avatar[POST /api/auth/profile/avatar]
    Show --> ProfileController[ProfileController]
    Update --> ProfileController
    Avatar --> ProfileController
    ProfileController --> User[(users)]
    User --> Province[province_id -> locations]
    User --> City[city_id -> locations]
    ProfileView --> Provinces[GET /api/locations/provinces]
    ProfileView --> Cities[GET /api/locations/{provinceId}/cities]
```

مایگریشن پروفایل ستون‌های `nickname`, `national_code` (unique و nullable)، `birth_date`, `iban`, `avatar`, `province_id`, `city_id` و `show_phone_publicly` را به `users` اضافه می‌کند. روابط `User::province()` و `User::city()` به جدول `locations` با `nullOnDelete` وصل هستند و `ProfileController@show` پروفایل را همراه استان و شهر برمی‌گرداند.

تمام endpointهای پروفایل زیر `auth:sanctum` قرار دارند. `PUT /api/auth/profile` ایمیل را با استثنای کاربر جاری، شبا را با فرمت `IR` + 24 رقم، تاریخ تولد و ارتباط استان/شهر اعتبارسنجی می‌کند. برای کاربر `is_verified=true`، نام رسمی و کد ملی از payload حذف می‌شوند و قابل بازنویسی نیستند؛ موبایل نیز فقط خواندنی است و در update پذیرفته نمی‌شود. `POST /api/auth/profile/avatar` فقط تصویرهای مجاز تا 2MB را در `storage/app/public/avatars` ذخیره می‌کند. رخدادهای به‌روزرسانی با تگ `[ProfileController:update]` لاگ می‌شوند.

### Profile form visual system

`ProfileView` از یک هدر جمع‌وجور خاکستری با آواتار 64 پیکسلی، نام، موبایل و بج احراز هویت استفاده می‌کند. ورودی فایل native با کلاس `hidden` مخفی است و دکمه دوربین آن را به‌صورت programmatic باز می‌کند. اطلاعات هویتی در گرید دو ستونه با `Select`، `DatePicker` و `ToggleSwitch` PrimeVue نمایش داده می‌شوند؛ شبا در ردیف مالی مستقل با پیشوند ثابت `IR` و نام بانک استخراج‌شده از کد سه‌رقمی قرار دارد. کارت حریم خصوصی و دکمه ذخیره نیز با borderهای خنثی و رنگ سازمانی `#A62626` با سبک مینیمال پنل هماهنگ هستند.

## Jalali date conversion architecture

```mermaid
flowchart LR
    PersianPicker[PersianDatePicker: YYYY/MM/DD] --> ProfileForm[ProfileView.form.birth_date]
    ProfileForm -->|1370/01/01| Update[PUT /api/auth/profile]
    Update --> TraitSet[HasJalaliDates.setAttribute]
    TraitSet -->|1991-03-21| Database[(users.birth_date: DATE)]
    Database --> TraitArray[HasJalaliDates.toArray]
    TraitArray -->|1370/01/01| ProfileAPI[GET /api/auth/profile]
    ProfileAPI --> PersianPicker
```

`app/Traits/HasJalaliDates.php` لایه تبدیل مرکزی تاریخ است و بدون وابستگی به پکیج اضافی کار می‌کند. `setAttribute` برای فیلدهای اعلام‌شده در `$jalaliDateFields` ورودی‌های `YYYY/MM/DD` یا `YYYY-MM-DD` جلالی را شناسایی و با الگوریتم تبدیل جلالی به میلادی، به مقدار قابل ذخیره در Carbon تبدیل می‌کند. `toArray` همان فیلدها را هنگام serialization از مقدار Gregorian/Carbon به `YYYY/MM/DD` شمسی برمی‌گرداند. این Trait روی `User` برای `birth_date` و روی `Advertisement` برای `created_at` و `updated_at` فعال است.

`PersianDatePicker.vue` مقدار مدل را همیشه به‌صورت رشته شمسی نگه می‌دارد، تقویم RTL با ماه‌های فارسی، انتخاب سال/ماه، روز جاری و انتخاب روز را ارائه می‌کند و مستقیماً همان مقدار را به `authService.updateProfile` می‌فرستد. تست `JalaliDateConversionTest` ذخیره میلادی و پاسخ شمسی endpoint پروفایل را کنترل می‌کند و `PersianDatePicker.test.js` event انتخاب روز را بررسی می‌کند.

```mermaid
flowchart TD
    App[Vue app] --> Router[Vue Router]
    Router --> Home[/]
    Router --> UserRoute[/login/user/authentication]
    Router --> AdminRoute[/admin/dashboard]
    UserRoute --> UserGuard{authenticated}
    AdminRoute --> AuthGuard{authenticated + Spatie role}
    AuthGuard -->|Super Admin or Operator| AdminLayout[BaseDashboardLayout]
    AuthGuard -->|regular user| UserRoute
    UserGuard --> UserLayout[BaseDashboardLayout]
    UserLayout --> UserMenu[AppMenu: userMenu]
    AdminLayout --> AdminMenu[AppMenu: adminMenu]
    UserLayout --> UserStore[useUserAds]
    AdminLayout --> AdminStore[useAdminAds]
    UserStore --> UserAPI[/api/user/ads]
    AdminStore --> AdminAPI[/api/admin/ads]
```

مسیر ادمین با navigation guard فرانت‌اند فقط برای نقش‌های `Super Admin` یا `Operator` قابل ورود است و مسیر پنل کاربر برای هر کاربر احراز‌شده جدا نگه داشته شده است. این guard جایگزین مجوز سمت Laravel نیست؛ endpointهای API باید همچنان با middleware احراز هویت و Spatie محافظت شوند. تست‌های این مرز در `resources/js/router/router.test.js` و تست رندر منوها در `resources/js/components/AppMenu.test.js` قرار دارند.

## Logging architecture

لاگینگ در هر دو لایه ساختاری و بدون داده حساس است. در Laravel، اکشن‌های `AuthController` هنگام ورود، پیش/پس از خواندن یا نوشتن cache و پایگاه‌داده، پایان موفق و خطاها با `Log::info`، `Log::warning` و `Log::error` ثبت می‌شوند. هر رویداد شامل `function`، `user_id`، `payload` محدودشده و `trace` است؛ کد OTP و توکن هرگز در لاگ نوشته نمی‌شوند.

در Vue، `resources/js/utils/logger.js` لاگر مرکزی با قالب `[ModuleName:functionName]` است. interceptorهای Axios، درخواست‌های `AuthModal`، اکشن‌های `useUserAds` و `useAdminAds`، توابع احراز هویت و navigation guard از آن استفاده می‌کنند. لاگ‌های `info` در build production خاموش‌اند و `warn`/`error` برای تشخیص خطا باقی می‌مانند. این لاگر فقط به console می‌نویسد و هیچ assertion یا خروجی API را تغییر نمی‌دهد؛ بنابراین با تست‌های Vitest و PHPUnit تداخل ندارد.
