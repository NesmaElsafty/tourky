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

    'routes' => [
        'list_retrieved' => 'تم جلب المسارات بنجاح.',
        'created' => 'تم إنشاء المسار بنجاح.',
        'updated' => 'تم تحديث المسار بنجاح.',
        'retrieved' => 'تم جلب المسار بنجاح.',
        'deleted' => 'تم حذف المسار بنجاح.',
        'not_found' => 'المسار غير موجود أو غير متاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
    ],

    'points' => [
        'list_retrieved' => 'تم جلب النقاط بنجاح.',
        'created' => 'تم إنشاء النقطة بنجاح.',
        'updated' => 'تم تحديث النقطة بنجاح.',
        'retrieved' => 'تم جلب النقطة بنجاح.',
        'deleted' => 'تم حذف النقطة بنجاح.',
        'not_found' => 'النقطة غير موجودة أو غير متاحة.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
    ],

    'times' => [
        'list_retrieved' => 'تم جلب أوقات الالتقاط بنجاح.',
        'created' => 'تم إنشاء وقت الالتقاط بنجاح.',
        'updated' => 'تم تحديث وقت الالتقاط بنجاح.',
        'retrieved' => 'تم جلب وقت الالتقاط بنجاح.',
        'deleted' => 'تم حذف وقت الالتقاط بنجاح.',
        'not_found' => 'وقت الالتقاط غير موجود أو غير متاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
    ],

    'terms' => [
        'list_retrieved' => 'تم جلب البنود بنجاح.',
        'created' => 'تم إنشاء البند بنجاح.',
        'updated' => 'تم تحديث البند بنجاح.',
        'retrieved' => 'تم جلب البند بنجاح.',
        'deleted' => 'تم حذف البند بنجاح.',
        'not_found' => 'البند غير موجود أو غير متاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
        'type_labels' => [
            'terms_conditions' => 'الشروط والأحكام',
            'privacy_policy' => 'سياسة الخصوصية',
            'FAQ' => 'الأسئلة الشائعة',
        ],
    ],

    'notifications' => [
        'list_retrieved' => 'تم جلب الإشعارات بنجاح.',
        'created' => 'تم إنشاء الإشعار بنجاح.',
        'updated' => 'تم تحديث الإشعار بنجاح.',
        'retrieved' => 'تم جلب الإشعار بنجاح.',
        'deleted' => 'تم حذف الإشعار بنجاح.',
        'not_found' => 'الإشعار غير موجود أو غير متاح.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
    ],

    'reservations' => [
        'admin_list_retrieved' => 'تم جلب الحجوزات بنجاح.',
        'admin_status_updated' => 'تم تحديث حالة الحجز بنجاح.',
        'client_upcoming_retrieved' => 'تم جلب الحجوزات القادمة بنجاح.',
        'client_history_retrieved' => 'تم جلب سجل الحجوزات بنجاح.',
        'created' => 'تم إنشاء الحجز بنجاح.',
        'cancelled' => 'تم إلغاء الحجز بنجاح.',
        'deleted' => 'تم حذف الحجز بنجاح.',
        'not_found' => 'الحجز غير موجود.',
        'server_error' => 'حدث خطأ ما. يرجى المحاولة لاحقاً.',
        'status_invalid' => 'يجب أن تكون الحالة مؤكدة أو ملغاة.',
        'invalid_time' => 'وقت الالتقاط المحدد غير صالح.',
        'inactive_time' => 'وقت الالتقاط هذا غير متاح.',
        'inactive_route' => 'هذا المسار غير متاح للحجز.',
        'invalid_date_past' => 'يجب أن يكون التاريخ والوقت في المستقبل.',
        'duplicate_reservation' => 'لديك بالفعل حجز لهذا الوقت في هذا اليوم.',
        'already_cancelled' => 'هذا الحجز ملغى بالفعل.',
        'cannot_cancel' => 'لا يمكن إلغاء هذا الحجز.',
        'client_only' => 'يمكن لحسابات العملاء فقط إنشاء الحجوزات.',
        'validation_scope_required' => 'يرجى تحديد نوع القائمة (upcoming أو history).',
        'validation_scope_invalid' => 'نوع القائمة غير صالح. استخدم upcoming أو history.',
        'validation_date_past' => 'يجب أن يكون التاريخ اليوم أو يوماً لاحقاً.',
        'validation_time_id' => 'يرجى اختيار وقت التقاط صالح.',
        'validation_status_required' => 'حقل الحالة مطلوب.',
        'validation_status_in' => 'يجب أن تكون الحالة مؤكدة أو ملغاة.',
        'status_labels' => [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'cancelled' => 'ملغى',
        ],
    ],
];
