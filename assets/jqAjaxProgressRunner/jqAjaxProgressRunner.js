/**
 * @file: jquery plugin 'jqAjaxProgressRunner'
 * @version: 1.0.0
 *
 * @author: miroslav curcic <https://tekod.com>
 * @repository: https://github.com/tekod/jqAjaxProgressRunner
 * @licence: MIT
 */

;(function ($) {

    $.fn.ajaxProgressRunner = function (opts) {

        if (this.length > 1) {
            this.each(function () { $(this).ajaxProgressRunner(opts) });
            return this;
        }

        var conf = $.extend({
            html: '<div class="jq_apr"><div class="progress"><div class="progress-fill"></div><div class="progress-text"></div></div><span class="run-report"></span></div>',
            ajaxURL: '',
            classRunning: 'running',
        }, opts, $(this).data());

        var plugin = this;
        var $button = $(this);
        var step = 0;
        var echo = null;


        this.init = function () {
            $button.after($(conf.html));
            $button.closest('form').on('submit', this.onSubmit);
            return this;
        };


        // handle form submission
        this.onSubmit = function (ev) {
            ev.preventDefault();
            if (!$button.hasClass(conf.classRunning)) {
                plugin.updateReport('', false);
                plugin.updatePercent(0);
                step = 0;
                echo = null;
                plugin.sendRequest();
                $button.toggleClass(conf.classRunning, true);
            }
        };


        // dispatch "run" request
        this.sendRequest = function() {
            step++;
            var data = $button.closest('form').serializeArray();
            data.push({name:'step', value:step});
            data.push({name:'echo', value:echo});
            $.ajax(conf.ajaxURL, {
                type : 'POST',
                dataType : 'json',
                data : data,
                success : plugin.onResponse,
                error: function(jqXHR, status, err) {
                    plugin.updateReport(status + ': ' + err, true);
                    $button.toggleClass(conf.classRunning, false);
                },
            });
        };


        // handle response
        this.onResponse = function(response) {
            // skip on ajax error
            if (!response.success) {
                plugin.updateReport('Error sending ajax request: ' + JSON.stringify(response), true);
                $button.toggleClass(conf.classRunning, false);
                return;
            }
            // skip on soft failure
            if (!response.data.success) {
                plugin.updateReport('Error sending ajax request: ' + response.data.error, true);
                $button.toggleClass(conf.classRunning, false);
                return;
            }
            // fire custom event
            $button.trigger('ajaxProgressRunnerResponse', response.data.data);
            // skip if not done
            if (!response.data.data.done) {
                echo = response.data.data.echo;
                plugin.sendRequest(); // recursion
                plugin.updatePercent(response.data.data.progress);
                return;
            }
            // it is done
            $button.toggleClass(conf.classRunning, false);
            plugin.updateReport(response.data.data.report || 'Finished.', false);
            plugin.updatePercent(response.data.data.progress);
        };


        // display progress gauge
        this.updatePercent = function(percent) {
            percent = Math.max(0, Math.min(100, parseInt(percent) || 0));
            var $progress = $button.closest('form').find('.progress');
            var multiplier = $progress.width() / 100;
            $progress.parent().css('display', 'inline-block');
            $('.progress-fill', $progress).css('width', (percent * multiplier) + 'px');
            $('.progress-text', $progress).text(percent + '%');
        };

        // display response message
        this.updateReport = function(text, asError) {
            const $Container = $button.closest('form').find('.run-report');
            $Container.html(text);
            $Container.toggleClass('error', asError);
        };

        // call init
        return this.init();
    }
})(jQuery);