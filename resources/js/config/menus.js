export const userMenu = [
    { label: 'آگهی‌های من', icon: 'pi pi-list', to: { name: 'user.my-ads' } },
    { label: 'نشان‌ها و یادداشت‌ها', icon: 'pi pi-bookmark', to: { name: 'user.bookmarks' } },
    { label: 'پروفایل و شماره تماس', icon: 'pi pi-user', to: { name: 'user.profile' } },
    { label: 'بازدیدهای اخیر', icon: 'pi pi-history', to: { name: 'user.history' } },
    { label: 'تایید هویت و مدارک', icon: 'pi pi-id-card', to: { name: 'user.verification' } },
    { label: 'تنظیمات حساب کاربری', icon: 'pi pi-cog', to: { name: 'user.settings' } },
];

export const adminMenu = [
    { label: 'بررسی و تأیید آگهی‌ها', icon: 'pi pi-check-square', to: { name: 'admin.dashboard' } },
    { label: 'بررسی احراز هویت کاربران', icon: 'pi pi-id-card', to: { name: 'admin.verifications' } },
    { label: 'مدیریت بانک‌ها و طرح‌ها', icon: 'pi pi-building', to: { name: 'admin.dashboard', hash: '#banks' } },
    { label: 'کاربران و گزارشات', icon: 'pi pi-chart-bar', to: { name: 'admin.dashboard', hash: '#reports' } },
];