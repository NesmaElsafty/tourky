<?php

return [
    'auth' => [
        'wrong_credentials' => 'بيانات الدخول غير صحيحة.',
        'unauthorized' => 'غير مصرح.',
    ],

    'role' => [
        'unauthorized_admin' => 'غير مصرح لهذا الدور (مدير).',
        'unauthorized_captain' => 'غير مصرح لهذا الدور (كابتن).',
        'unauthorized_client' => 'غير مصرح لهذا الدور (عميل).',
    ],

    'admin' => [
        'registered' => 'تم تسجيل المدير بنجاح.',
        'logged_in' => 'تم تسجيل دخول المدير بنجاح.',
        'logged_out' => 'تم تسجيل خروج المدير بنجاح.',
        'profile_retrieved' => 'تم جلب ملف المدير بنجاح.',
        'update_profile_success' => 'تم تحديث ملف المدير بنجاح.',
    ],

    'roles' => [
        'list_retrieved' => 'تم جلب الأدوار بنجاح.',
        'created' => 'تم إنشاء الدور بنجاح.',
        'updated' => 'تم تحديث الدور بنجاح.',
        'retrieved' => 'تم جلب الدور بنجاح.',
        'deleted' => 'تم حذف الدور بنجاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
    ],

    'captain' => [
        'registered' => 'تم تسجيل الكابتن بنجاح.',
        'registration_failed' => 'فشل تسجيل الكابتن.',
        'logged_in' => 'تم تسجيل دخول الكابتن بنجاح.',
        'login_failed' => 'فشل تسجيل دخول الكابتن.',
        'profile_failed' => 'فشل جلب ملف الكابتن.',
        'update_profile_failed' => 'فشل تحديث ملف الكابتن.',
        'logged_out' => 'تم تسجيل خروج الكابتن بنجاح.',
        'logout_failed' => 'فشل تسجيل خروج الكابتن.',
    ],

    'client' => [
        'registered' => 'تم تسجيل العميل بنجاح.',
        'logged_in' => 'تم تسجيل دخول العميل بنجاح.',
        'logged_out' => 'تم تسجيل خروج العميل بنجاح.',
    ],

    'media' => [
        'avatar_uploaded' => 'تم رفع الصورة الشخصية.',
        'avatar_removed' => 'تم حذف الصورة الشخصية.',
        'file_uploaded' => 'تم رفع الملف.',
        'file_deleted' => 'تم حذف الملف.',
    ],

    'cars' => [
        'list_retrieved' => 'تم جلب السيارات بنجاح.',
        'created' => 'تم إنشاء السيارة بنجاح.',
        'updated' => 'تم تحديث السيارة بنجاح.',
        'retrieved' => 'تم جلب السيارة بنجاح.',
        'deleted' => 'تم حذف السيارة بنجاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
        'type_labels' => [
            'sedan' => 'سيدان',
            'microbus' => 'ميكروباص',
        ],
    ],
];
