$nc(function () {
    if ($('.nc-navbar .nc-quick-menu li.nc--active').index() == 1) {

        $("body").append('<div id="actionCropPopup"><div style="padding-top: 20px;" class="nc_admin_form_menu"><h2>Редактирование изображения</h2><div class="nc_admin_form_menu_hr"></div></div><div class="container nc_admin_form_body nc-admin"><div class="actionImage imageContainer"></div><div class="ratioContainer">Выберите размер превью картинки для редактирования<div class="actionRatio"></div></div></div><div class="nc_admin_form_buttons"><button class="nc_admin_metro_button_delete nc--left nc-btn nc--red" type="button">Удалить</button><input class="preview" type="file" accept="image/*" data-url="/netcat/admin/crop_image.php" name="new_image" /><button class="nc_admin_metro_button nc-btn nc--blue" type="button">Сохранить</button><button class="nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right" type="button">Отмена</button></div></div>');

        $("img.cropable").each(function () {
            var image_obj = $(this);
            $(this).wrap('');
            $(this).css('position', 'relative');
            //$(this).after('<img class="nc-icon-cut" src="/netcat/admin/skins/v5/img/icon-50-cut-image.png"/>');
            $(this).after('<div class="nc-icon-cut"><i class="nc-icon nc--edit" title="Редактировать"></i></div>');
            $(this).parent().find('.nc-icon-cut').click(function (e) {
                e.preventDefault();
                d = new Date();
                modalWindowSize = null;
                nc_register_modal_resize_handler();

                nc_remove_content_for_modal();

                $nc('#actionCropPopup').modal({
                    onShow: function (dialog) {
                        var container = dialog.container;
                        if (modalWindowSize) {
                            var currentLeft = parseInt(container.css('left'));
                            var currentWidth = container.width();

                            var currentTop = parseInt(container.css('top'));
                            var currentHeight = container.height();

                            container.css({
                                width: modalWindowSize.width,
                                height: modalWindowSize.height,
                                left: currentLeft + (currentWidth - modalWindowSize.width) / 2,
                                top: currentTop + (currentHeight - modalWindowSize.height) / 2
                            }).addClass('simplemodal-container-fixed-size');
                        }
                        else {
                            container.removeClass('simplemodal-container-fixed-size');
                            $nc(window).resize();
                        }

                        imageCrop.init({
                            img_obj: image_obj,
                            source: image_obj.attr('src') + "?" + d.getTime(),
                            ratio: nc_crop_ratio, // Массив предустановленных размеров
                            popup: $('#actionCropPopup') // Объект всплывающего окна
                        });
                    },
                    closeHTML: "<a class='modalCloseImg'></a>",
                    onClose: function (e) {
                        $nc.modal.close();
                        $nc(document).unbind('keydown.simplemodal');
                        nc_remove_content_for_modal();
                    }
                });
            });
        });
    }
});
