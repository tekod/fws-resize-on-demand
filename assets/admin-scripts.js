
(function( $ ) {
	'use strict';

	var ROD = {

		// cache DOM elements
		$BtnDeleteThumbs: $('#fws_rod_utl_delete'),

		// initialize
		init: function() {
			$(".fws_rod .nav-tab").on('click', ROD.OnTabClick);
			$("#fws_rod_checkall").on('click', ROD.OnCheckAll);
			$('#fws_rod_utl_delete').ajaxProgressRunner({ajaxURL:window.ajaxurl});
			$('#fws_rod_utl_regenerate').ajaxProgressRunner({ajaxURL:window.ajaxurl});
		},

		// handle switching tabs
		OnTabClick: function(ev) {
			const slug = ev.target.id.split('_').reverse()[0];
			// set tabs class
			$(".fws_rod .nav-tab").each(function () {
				$(this).toggleClass("nav-tab-active", $(this).attr("id") === "fws_rod_tab_" + slug);
			});
			// set visibility of content
			$(".fws_rod .fws_rod_tab").each(function () {
				$(this).toggle($(this).attr("id") === "fws_rod_tabc_" + slug);
			});
			// set cookie
			const expires = new Date(Date.now() + 365 * 864e5).toUTCString();
			document.cookie = 'fws_rod_tab='+slug+'; expires='+expires+'; path=/';
		},

		// handle click on "check all"
		OnCheckAll: function() {
			$('#RodSettingsForm input[name="fws_ROD_Sizes[]"]:not(:disabled)').each(function(){
				$(this).prop('checked', !$(this).prop('checked'));
			});
		},

		// handle click on "delete thumbnails" button
		OnUtilDeleteThumbs: function (ev) {
			ev.preventDefault();
			if (!ROD.$BtnDeleteThumbs.hasClass('disabled')) {
				ROD.DeleteThumbsSendRequest(true);
				ROD.DeleteThumbsUpdatePercent(0, false);
				ROD.$BtnDeleteThumbs.toggleClass('disabled', true);
			}
		},

		// dispatch "run" request
		DeleteThumbsSendRequest: function(isInitial) {
			console.log('DeleteThumbsSendRequest');
			var data = ROD.$BtnDeleteThumbs.closest('form').serializeArray();
			data.push({name:'init', value:isInitial ? '1' : ''});
			console.log('aajax', data);
			$.ajax(window.ajaxurl, {
				type : 'POST',
				context : this,
				dataType : 'json',
				data : data,
				success : ROD.OnDeleteThumbsResponse,
				error: function(jqXHR, status, err) {
					ROD.DeleteThumbsUpdateReport(status + ': ' + err, true);
					ROD.$BtnDeleteThumbs.toggleClass('disabled', false);
				}
			});
		},

		// handle response
		OnDeleteThumbsResponse: function(response) {
			// skip on ajax error
			if (!response.success) {
				ROD.DeleteThumbsUpdateReport('Error sending ajax request: ' + JSON.stringify(response), true);
				ROD.$BtnDeleteThumbs.toggleClass('disabled', false);
				return;
			}
			// skip on soft failure
			if (!response.data.success) {
				ROD.DeleteThumbsUpdateReport('Error sending ajax request: ' + response.data.error, true);
				ROD.$BtnDeleteThumbs.toggleClass('disabled', false);
				return;
			}
			// skip if not done
			if (!response.data.data.done) {
				ROD.DeleteThumbsSendRequest(); // recursion, a sort of
				ROD.DeleteThumbsUpdatePercent(response.data.data.progress, response.data.data.done);
				return;
			}
			// it is done
			ROD.$BtnDeleteThumbs.toggleClass('disabled', false);
			ROD.DeleteThumbsUpdateReport('Finished.', false);
			ROD.DeleteThumbsUpdatePercent(response.data.data.progress, response.data.data.done);
		},


		// display progress gauge
		DeleteThumbsUpdatePercent: function(percent, done) {
			const $Progress = ROD.$BtnDeleteThumbs.closest('form').find('.progress');
			$Progress.parent().css('display', 'inline-block');
			$('.progress-fill', $Progress).css('width', (percent * 2) + 'px');
			$('.progress-text', $Progress).text(percent + '%');
		},

		// display response message
		DeleteThumbsUpdateReport: function(text, asError) {
			const $Container = ROD.$BtnDeleteThumbs.closest('form').find('.run-report');
			$Container.html(text);
			$Container.css('color', asError ? 'maroon' : 'green');
		},



	};

    // on DOM ready
    $(function() {
		if ($('.fws_rod').length === 0) {
			return;
		}
		ROD.init();
		window.FWS_ROD = ROD;
    });


/////////////////////////////////////



	/**
	 * File monitoring
	 */
	var FM= {

		// cached DOM elements
		$fmEnabled: null,
		$fmIntervalType: null,
		$fmIntervalTimeWrap: null,
		$fmRunProgress: null,
		$fmRunForm: null,
		$fmRunFormBtn: null,
		$fmRunReport: null,
		$fmLastReport: null,

		// main init
		init: function () {
			FM.initDOM();
			FM.SetupControls();
		},

		// initialize cached DOM variables
		initDOM: function () {
			FM.$fmEnabled = $('#smonitor-fmEnabled');
			FM.$fmIntervalType = $('.smonitor select[name=fmIntervalType]');
			FM.$fmIntervalTimeWrap = $('.smonitor .js-fm-interval-time-wrap');
			FM.$fmRunForm = $('.smonitor input[name=action][value=smonitor_fm_run]').closest('form');
			FM.$fmRunFormBtn = $('input[type=submit]', FM.$fmRunForm);
			FM.$fmRunProgress = $('.smonitor .progress');
			FM.$fmRunReport = $('.smonitor .js-fm-run-report');
			FM.$fmLastReport = $('.smonitor .js-fm-last-report');
		},

		SetupControls: function() {
			FM.$fmEnabled.on('change', FM.UpdateToggledRows);
			FM.UpdateToggledRows();
			FM.$fmIntervalType.on('change', function() {
				FM.$fmIntervalTimeWrap.toggle(FM.$fmIntervalType.val() === 'daily');
			});

		},

		UpdateToggledRows: function(ev) {
			HelperUpdateToggledRows($('#smonitor_tabc_file-monitoring form .togglable-row'), FM.$fmEnabled.prop('checked'), ev);
		},

	};




	/**
	 * Integrity checking
	 */
	var IC= {

		// cached DOM elements
		$icEnabled: null,
		$icIntervalType: null,
		$icIntervalTimeWrap: null,
		$icRunProgress: null,
		$icRunForm: null,
		$icRunFormBtn: null,
		$icRunReport: null,
		$icLastReport: null,

		// main init
		init: function () {
			IC.initDOM();
			IC.SetupControls();
		},

		// initialize cached DOM variables
		initDOM: function () {
			IC.$icEnabled = $('#smonitor-icEnabled');
			IC.$icIntervalType = $('.smonitor select[name=icIntervalType]');
			IC.$icIntervalTimeWrap = $('.smonitor .js-ic-interval-time-wrap');
			IC.$icRunForm = $('.smonitor input[name=action][value=smonitor_ic_run]').closest('form');
			IC.$icRunFormBtn = $('input[type=submit]', IC.$icRunForm);
			IC.$icRunProgress = $('.smonitor .progress');
			IC.$icRunReport = $('.smonitor .js-ic-run-report');
			IC.$icLastReport = $('.smonitor .js-ic-last-report');
		},

		SetupControls: function() {
			IC.$icEnabled.on('change', IC.UpdateToggledRows);
			IC.UpdateToggledRows();
			IC.$icIntervalType.on('change', function() {
				IC.$icIntervalTimeWrap.toggle(IC.$icIntervalType.val() === 'daily');
			});
			IC.$icRunForm.on('submit', function (ev) {
				ev.preventDefault();
				if (!IC.$icRunFormBtn.hasClass('disabled')) {
					IC.SendRunRequest();
					IC.$icRunFormBtn.toggleClass('disabled', true);
				}
			});
		},

		UpdateToggledRows: function(ev) {
			HelperUpdateToggledRows($('#smonitor_tabc_integrity-checking form .togglable-row'), IC.$icEnabled.prop('checked'), ev);
		},

		// dispatch "run" request
		SendRunRequest: function() {
			var data = IC.$icRunForm.serializeArray();
			$.ajax(window.ajaxurl, {
				type : 'POST',
				context : this,
				dataType : 'json',
				data : data,
				success : IC.OnRunResponse,
				error: function(jqXHR, status, err) {
					IC.UpdateReport(status + ': ' + err, true);
				},
				complete: function() {
					IC.$icRunFormBtn.toggleClass('disabled', false);
				}
			});
		},

		// handle response
		OnRunResponse: function(response) {
			// skip on ajax error
			if (!response.success) {
				IC.UpdateReport('Error sending ajax request: ' + JSON.stringify(response), true);
				return;
			}
			// skip on soft failure
			if (!response.data.success) {
				IC.UpdateReport('Error sending ajax request: ' + response.data.error, true);
				return;
			}
			// it is done
			IC.UpdateReport('Finished.', false);
			IC.$icLastReport.html('<br>' + response.data.data.report);
		},


		// display response message
		UpdateReport: function(text, asError) {
			IC.$icRunReport.html(text);
			IC.$icRunReport.css('color', asError ? 'maroon' : 'green');
		}
	};



})( jQuery );


