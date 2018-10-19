var imageCrop = {
    // Контейнер для исходного изображения
    $imageContainer: null,
    $imageObject: null,
    // Контейнер для блоков с пропорциями
    $ratioContainer: null,
    // Кнопка: закрыть окно
    $closeButton: null,
    // Кнопка: сохранить (передать параметры области изображения серверному скрипту)
    $saveButton: null,
    // Всплывающее окно
    $window: null,
    $isNew: null,
    $oldSrc: null,
    // Время задержки плавного появления / исчезания
    fadeDelay: 100,
    // Если высота окна больше высоты браузера, то уменьшаем ее и добавляем отступы
    padding: 0,
    // Если исходное изображение больше чем контейнер под него, то уменьшаем его до предельного значения
    limitWidthImageContainer: 480,
    limitHeightImageContainer: 320,
    // Ограничение по ширине для блока с пропорциями
    limitWidthRatioBlock: 100,
    // Доступ к api методам кропа изображения
    cropObject: null,
    // Адрес серверного скрипта обработки изображения
    scriptUrl: (window.NETCAT_PATH || '/netcat/') + 'admin/crop_image.php',
    
    /**
     * Присвоение переменных, вызов базовых методов
     */
    init: function (settings) {
        this.$window = settings.popup;
        this.$closeButton = $('.nc_admin_metro_button_cancel', this.$window);
        this.$saveButton = $('.nc_admin_metro_button', this.$window);
        this.$deleteButton = $('.nc_admin_metro_button_delete', this.$window);
        this.$imageContainer = $('.actionImage', this.$window);
        this.$ratioContainer = $('.actionRatio', this.$window);
        this.$imageObject = settings.img_obj;
        this.$isNew = 0;
        this.$oldSrc = settings.source;

        this.putImage(settings.source);
        this.putRatio(settings.ratio);
        this.initEvents();
    },
    /**
     * Кладем изображение, адрес которого получен из настроек, в контейнер, и уменьшаем его ширину, если она
     * больше чем отведенная под изображение (limitWidthImageContainer). Подключаем плагин Jcrop
     */
    putImage: function (src) {
        var self = this;
        $image = $('<img>').attr('src', src).load(function () {
            var realWidth = this.width,
                    realHeight = this.height;
            if (this.height > self.limitHeightImageContainer && this.height > this.width)
            {
                this.width = Math.ceil(self.limitWidthImageContainer / (this.height/this.width));
            } else
            if (this.width > self.limitWidthImageContainer)
            {
                this.width = self.limitWidthImageContainer;
            }

            self.$imageContainer.html(this);
            $nc(this).Jcrop({
                trueSize: [realWidth, realHeight]
            }, function () {
                self.cropObject = this;
                self.cropObject.realWidth = realWidth;
                self.cropObject.realHeight = realHeight;
            });
        });
   },
    /**
     * Заполняем контейнер доступными предустановками размеров (получаются из настроек).
     * В размерах могут присутствовать символы (например g130x120), поэтому очищаем их.
     */
    putRatio: function (ratio) {
        if (ratio.length == 0)
        {
            return;
        }
        var size = [],
                ratioHtml = '',
                width = 0,
                height = 0,
                dimension = '';
        ratioHtml += '<div class="ratioBlock actionRatioBlock" data-width="0" data-height="0" style="width: 100px; height: 100px;"><div style="width: 100px; height: 100px;">свободный размер</div></div>';
        for (var i = 0; i < ratio.length; i++)
        {
            size = ratio[i].split('x');
            width = size[0].replace(/\D/g, '');
            height = size[1].replace(/\D/g, '');
            dimension = width + 'x' + height;
            if (width > this.limitWidthRatioBlock)
            {
                height = Math.ceil(this.limitWidthRatioBlock / width * height);
                width = this.limitWidthRatioBlock;
            }
            ratioHtml += '<div class="ratioBlock actionRatioBlock" data-ratio="' + ratio[i] + '" data-width="' + size[0] + '" data-height="' + size[1] + '" style="width: ' + width + 'px; height: ' + height + 'px;"><div style="width: ' + width + 'px; height: ' + height + 'px;">' + dimension + '</div></div>';
        }
        this.$ratioContainer.html(ratioHtml);
   },
    /**
     * Показываем всплывающее окно, если оно не помещается по высоте, то уменьшаем ее
     */
    showWindow: function () {
        var height = this.$window.height(),
                windowHeight = $(window).height();
        if (height > windowHeight)
        {
            this.$window.css({
                'height': (windowHeight - this.padding),
                'marginTop': -(windowHeight - this.padding) / 2
            });
        }
        this.$window.fadeIn(this.fadeDelay);
    },
    /**
     * Закрываем всплывающее окно
     */
    closeWindow: function () {
        this.$window.fadeOut(this.fadeDelay);
    },
    /**
     * Передача параметров кропа серверному скрипту, выполнение определенных действий 
     * пока идет обработка изображения и по окончании обработки.
     */
    saveCropImage: function () {
        var self = this,
                coords = this.cropObject.tellSelect(),
                dimension = '';
        if ($('.actionRatioBlock.active', this.$ratioContainer).length > 0)
        {
            dimension = $('.actionRatioBlock.active', this.$ratioContainer).attr('data-ratio');
        }
        $.ajax({
            url: self.scriptUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'crop',
                is_new: self.$isNew,
                old_source: self.$oldSrc,
                source: $('img', self.$imageContainer).attr('src'),
                dimension: dimension,
                startX: Math.round(coords.x),
                startY: Math.round(coords.y),
                endX: Math.round(coords.x2),
                endY: Math.round(coords.y2),
                width: Math.round(coords.w),
                height: Math.round(coords.h),
            },
            beforeSend: function () {
                self.$saveButton.html('Сохранение изображения...');
            },
            complete: function () {
                d = new Date();
                if (self.$isNew === 0) {
                    $(self.$imageObject).attr("src", $('img', self.$imageContainer).attr('src') + "?" + d.getTime());
                } else {
                    $(self.$imageObject).attr("src", self.$oldSrc + "t=" + d.getTime());
                }
                $nc.modal.close();
            },
            success: function (data) {
            }
        });
    },
    deleteImage: function () {
        var self = this;
        $.ajax({
            url: self.scriptUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                formData: self.$imageObject.data(),
                action: 'delete',
                source: $('img', self.$imageContainer).attr('src')
            },
            complete: function () {
                d = new Date();
                $(self.$imageObject).addClass('no-image');
                $(self.$imageObject).attr("src", nc_edit_no_image);
                $nc.modal.close();
            },
            success: function (data) {
            }
        });
    },
	
    /**
     * Обработчики событий
     */
    initEvents: function () {
        var self = this;
        this.$closeButton.on('click', function () {
            $nc.modal.close();
        });
        this.$saveButton.on('click', function () {
            if (!self.$imageObject.hasClass('no-image')) {
                self.saveCropImage();
            } else {
                $nc.modal.close();
            }
        });
        this.$deleteButton.on('click', function () {
            if (!self.$imageObject.hasClass('no-image')) {
                self.deleteImage();
            } else {
                $nc.modal.close();
            }
        });
        $nc('.preview').fileupload({
            dataType: 'json',
            formData: self.$imageObject.data(),
            done: function (e, data) {
                d = new Date();
                self.$isNew = 1;
                self.putImage(data.result.url+"?"+d.getTime());
                self.$imageObject.removeClass('no-image');
            }
        });
        $('.actionRatioBlock', this.$ratioContainer).on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');

            var pre_width = Math.round($(this).attr('data-width').replace(/\D/g, ''));
            var pre_height = Math.round($(this).attr('data-height').replace(/\D/g, ''));

            if (pre_width > 0 && pre_height > 0) {
                self.cropObject.setOptions({
                    aspectRatio: pre_width / pre_height
                });
                var setX = Math.round((self.cropObject.realWidth / 2 - pre_width / 2));
                var setY = Math.round((self.cropObject.realHeight / 2 - pre_height / 2));
                self.cropObject.setSelect([setX, setY, setX + pre_width, setY + pre_height]);
            } else {
                self.cropObject.setOptions({
                    aspectRatio: 0 / 0
                });
            }
        });
    }
};