(function () {
    'use strict';
    window.deleteConfirmMsg = function (entity, count) {
        var suffix = ' kalıcı olarak tamamen silinecektir; ilişkili kayıtlar da sistemden kaldırılacaktır. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?';
        if (typeof count === 'number' && count > 0) {
            return 'Seçili ' + count + ' ' + entity + suffix;
        }
        return 'Bu ' + entity + suffix;
    };
})();
