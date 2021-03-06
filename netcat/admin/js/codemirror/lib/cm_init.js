$nc.fn.codemirror = function (init_options) {

    var action = 'render';
    if (typeof init_options == 'string') {
        action = init_options;
        init_options = null;
    }

    function getEditorTypeById(id) {
        var res = 'application/x-httpd-php';
        switch (id) {
            case 'Query':
                res = 'text/x-mysql';
                break;
            case 'f_CSS':
                res = 'text/css';
                break;
        }
        return res;
    }

    function getEditorFromTextArea(el, extra_options) {
        var options = init_options;
        if (!options) {
            options = $nc(el).data('codemirror_options');
        }
        else {
            $nc(el).data('codemirror_options', options);
        }
        var mode = getEditorTypeById($nc(el).attr('id')),
            param = {
                lineWrapping: false,
                lineNumbers: true,
                electricChars: false,
                mode: mode,
                indentWithTabs: false,
                matchBrackets: true,
                smartIndent: false
                //iOSselection: false
            };
        if (extra_options) {
            $nc.extend(param, extra_options);
        }

        var onChangeCallback = function () {
            if (parent.mainView && parent.mainView.remindSaveTrigger && parent.REMIND_SAVE == '1') {
                parent.mainView.chan = 1;
                parent.mainView.displayStar(1);
            }
            if ($nc.modal && $nc.modal.isOpen()) {
                $nc.modal.nc_modal_confirm = true;
            }
        };

        if (mode == 'text/x-php' || mode == 'application/x-httpd-php') {

            param.extraKeys = {
                'Ctrl-Space': function (editor) {
                    CodeMirror.simpleHint(
                        editor,
                        CodeMirror.netcatHint,
                        options.CMAutocomplete,
                        options.CMHelp,
                        true // forced
                    );
                },
                'Enter': function (editor) {
                    if (editor.complete && editor.complete.visible) {
                        editor.complete.sel.handleKeyDown({keyCode: 13});
                        return;
                    }
                    editor.execCommand('newlineAndIndent');
                },
                'Down': function (editor) {
                    if (editor.complete && editor.complete.visible) {
                        setTimeout(function () {
                            editor.complete.sel.focus();
                            if (editor.complete.sel.options.length > 1) {
                                editor.complete.sel.selectedIndex = 1;
                                editor.complete.sel.children[1].selected = true;
                            }
                            editor.complete.sel.handleKeyDown({keyCode: 40});
                        }, 50);
                        return;
                    }
                    editor.execCommand('goLineDown');
                },
                'Esc': function (editor) {
                    if (editor.complete && editor.complete.visible) {
                        editor.complete.sel.handleKeyDown({keyCode: 27});
                        return;
                    }
                    if ($nc(el).closest('.cm_wrapper').hasClass('cm_fullscreen')) {
                        $nc(el).closest('.cm_wrapper').find('.option_fullscreen input').trigger('click');
                        return false;
                    }
                    editor.execCommand('goLineDown');
                },
                "Ctrl-F11": function (editor) {
                    $nc(el).closest('.cm_wrapper').find('.option_fullscreen input').trigger('click');
                },
                "Tab": function (editor) {
					if (editor.somethingSelected()) {
						editor.indentSelection("add");
					}
					else {
						editor.replaceSelection("    ", "end");
					}
                }
            };
            param.onBlur = function (editor) {
                if (editor.complete && editor.complete.sel) {
                    setTimeout(function () {
                        if (document.activeElement === editor.complete.sel) {
                            return;
                        }
                        editor.complete.sel.closeCompletion();
                    });
                }
            };
            param.onCursorActivity = function (editor) {
                if (editor && editor.complete && editor.complete.visible) {
                    var cur = editor.getCursor();
                    var res_cur = editor.completionResult.to;
                    if (cur.line != res_cur.line || cur.ch != res_cur.ch) {
                        editor.complete.sel.closeCompletion();
                    }
                }
            };
        }

        if (typeof param.onChange == 'undefined') {
            param.onChange = onChangeCallback;
        }

        var ed = CodeMirror.fromTextArea(el, param);

        if (options.CMAutocomplete) {
            ed.on("change", function (ed, change) {
                onChangeCallback();
                CodeMirror.simpleHint(
                    ed,
                    CodeMirror.netcatHint,
                    options.CMAutocomplete,
                    options.CMHelp,
                    false // not forced
                );
            });
        }

        ed.autoCompletionData = $nc(el).data('autoCompletionData');
        return ed;
    }

    function getVerticalPadding(el) {
        var $el = $nc(el);
        return parseInt($el.css('padding-top'), 10) + parseInt($el.css('padding-bottom'), 10);
    }

    function getHorizontalPadding(el) {
        var $el = $nc(el);
        return parseInt($el.css('padding-left'), 10) + parseInt($el.css('padding-right'), 10);
    }

    function showCMEditor(textarea, extra_options) {
        extra_options = extra_options || {};

        var $textarea = $nc(textarea);

        if ($textarea.data('codemirror')) {
            var cur = $textarea.data('codemirror').getCursor();
            hideCMEditor(textarea);
            $nc(function () {
                showCMEditor(textarea, extra_options);
                var ed = $textarea.data('codemirror');
                ed.focus();
                ed.setCursor(cur);
            });
            return;
        }

        var h = $textarea.height() + getVerticalPadding($textarea),
            w = $textarea.width() + getHorizontalPadding($textarea);

        if (!extra_options.lineWrapping) {
            extra_options.lineWrapping = $textarea.closest('.cm_wrapper').find('.option_wrap input:checked').length > 0;
        }

        var ced = getEditorFromTextArea(textarea, extra_options);
        ced.id = $textarea.attr('id');
        var scrollEl = $nc(ced.getScrollerElement());
        scrollEl.height(h);
        scrollEl.closest('.CodeMirror').width(w);
        ced.refresh();
        $nc(textarea).data('codemirror', ced).addClass('has_codemirror');
        $textarea.siblings('.cm_switcher').find('.option_enable input').prop('checked', true);
    }

    function hideCMEditor(textarea) {
        var $textarea = $nc(textarea),
            ced = $textarea.data('codemirror');
        if (ced) {
            var h = $nc(ced.getScrollerElement()).height() - getVerticalPadding($textarea);
            ced.toTextArea();
            $textarea.height(h);
            $nc(textarea).data('codemirror', null).removeClass('has_codemirror');
        }
        $textarea.siblings('.cm_switcher').find('.option_enable input').prop('checked', false);
    }

    function toggleCMEditor(el) {
        var is_on = $nc(el).data('codemirror');
        is_on ? hideCMEditor(el) : showCMEditor(el);
    }

    var cm_textareas = this;

    function render() {

        $nc('.completionData').each(function () {
            CodeMirror.importCompletionData($nc(this).data('completionData'), cm_textareas);
        });
        CodeMirror.importCompletionData(init_options.autoCompletionData, cm_textareas);

        $nc(window).resize(function () {
            cm_textareas.each(function () {
                var $this = $nc(this),
                    parents = $this.parentsUntil('form'),
                    width = $this.closest('form').width();
                parents.each(function () { width -= getHorizontalPadding(this); });
                $nc(this.parentNode).find('div.CodeMirror').width(width);
            })
        });

        cm_textareas.each(function (ind, el) {
            if ($nc(el).data('codemirror_rendered')) {
                return;
            }

            var option_fields = {
                enable: init_options.label_enable,
                wrap: init_options.label_wrap,
                fullscreen: init_options.label_fullscreen
            };

            $nc(el).wrap('<div class="cm_wrapper"></div>');
            var cm_wrapper = $nc(el).parent();
            var cm_switcher = $nc('<div class="cm_switcher"></div>');
            for (var opt_name in option_fields) {
                cm_switcher.append(
                    '<span class="option option_' + opt_name + '">' +
                    '<input type="checkbox" id="cmtext_' + ind + '_' + opt_name + '" />' +
                    '<label for="cmtext_' + ind + '_' + opt_name + '">' + option_fields[opt_name] + '</label>&nbsp&nbsp;&nbsp;' +
                    '</span>'
                );
            }

            $nc('.option_enable input', cm_switcher).click(function () {
                toggleCMEditor(el);
            });

            $nc('.option_wrap input', cm_switcher).click(function () {
                var cm = $nc(el).data('codemirror'),
                    wrap = $nc(this).prop('checked');
                if (cm) {
                    var cursor = cm.getCursor();
                    cm.setOption('lineWrapping', wrap);
                    cm.focus();
                    cm.setCursor(cursor);
                }
                $nc(el).prop('wrap', wrap ? 'soft' : 'off');
            });

            $nc('.option_fullscreen input', cm_switcher).change(function () {

                var cm = $nc(el).data('codemirror'),
                    cur;
                if (cm) {
                    cur = cm.getCursor();
                }

                hideCMEditor(el);

                var is_iframe = window.self != window.top;

                var wrap = $nc(this).closest('.cm_wrapper');

                function setIframeFullscreen() {
                    hideCMEditor(el);

                    if (is_iframe) {
                        var top_body = $nc(window.top.document.body),
                            top_html = $nc(window.top.document).find('html');
                        var main_view_content = $nc('#mainViewContent', top_body);
                        if (!main_view_content.hasClass('fullscreen')) {
                            main_view_content.addClass('fullscreen');
                            window.top.resize_layout();
                        }
                        top_html.data({
                            'overflow_x_before_fullscreen': top_html.css('overflow-x'),
                            'overflow_y_before_fullscreen': top_html.css('overflow-y')
                        });
                        top_html.css('overflow', 'hidden');
                        top_body.css('overflow', 'hidden');
                    }
                    $nc('body').css('overflow', 'hidden');

                    wrap.addClass('cm_fullscreen');
                    wrap.css({
                        position: 'fixed',
                        top: '0',
                        left: '0',
                        height: $nc(window.top).height() + 'px',
                        width: '100%',
                        zIndex: 15000,
                        background: '#FFF'
                    });
                    $nc('textarea', wrap).css({
                        height: wrap.height() - 40 + 'px',
                        width: '100%'
                    });
                    showCMEditor(el);
                }

                $nc(window).unbind('resize.cmfull');

                if (this.checked) {
                    wrap.data('placeholder', $nc('<div class="cm_placeholder"></div>').insertAfter(wrap));
                    //b.append(wrap);
                    setIframeFullscreen();
                    $nc(window).bind('resize.cmfull', function () {
                            setTimeout(setIframeFullscreen, 100);
                        }
                    );
                    $nc(document).bind('keydown.cmfull', function (e) {
                        if (e.keyCode !== 27) {
                            return;
                        }
                        $nc(el).closest('.cm_wrapper').find('.option_fullscreen input').trigger('click');
                        e.preventDefault();
                        return false;
                    });

                }
                else {
                    if (is_iframe) {
                        var top_body = $nc(window.top.document.body),
                            top_html = $nc(window.top.document).find('html');
                        $nc('#mainViewContent', top_body).removeClass('fullscreen');
                        window.top.resize_layout();
                        setTimeout(function() { // execute after this function has finished
                            top_html.css({
                                'overflow-x': top_html.data('overflow_x_before_fullscreen'),
                                'overflow-y': top_html.data('overflow_y_before_fullscreen')
                            });
                        }, 20);
                    }
                    $nc('body').css('overflow', 'auto');

                    wrap.insertAfter(wrap.data('placeholder'));
                    wrap.data('placeholder').remove();
                    wrap.css({
                        width: '',
                        height: '',
                        position: 'static',
                        background: '#FFF'
                    });
                    wrap.removeClass('cm_fullscreen');
                    wrap.find('textarea').css({width: '', height: ''});
                    showCMEditor(el);
                    $nc(document.body).scrollTop(wrap.offset().top);
                    $nc(document).unbind('keydown.cmfull');
                }
                setTimeout(function () {
                    var ed = $nc(el).data('codemirror');
                    if (ed) {
                        ed.focus();
                        if (cur) { ed.setCursor(cur); }
                    }
                }, 10);
            });
            $nc(el).after(cm_switcher);
            $nc(el).data('codemirror_rendered', true);

            if (init_options.CMDefault) {
                // setTimeout нужен для IE9
                setTimeout(function () {
                    $nc('.option_enable input', cm_switcher).click().prop('checked', true);
                }, 1);
            }
        });
    }

    if (action == 'render') {
        render();
        return;
    }

    var action_params = arguments[1];

    cm_textareas.each(function () {
        var ed = $nc(this).data('codemirror');
        if (ed) {
            switch (action) {
                case 'setValue':
                    var new_value = action_params;
                    if (new_value === undefined) {
                        new_value = this.value;
                    }
                    ed.setValue(new_value);
                    break;
                case 'save':
                    ed.save();
                    break;
            }
        }
    });
};
