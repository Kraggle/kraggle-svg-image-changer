// const $ = jQuery;

$(() => {
	// initial collapse heights
	$('.kgl-collapse').each(function() {
		$(this).data('height', $(this).outerHeight() + 16);
		if (!$(this).hasClass('open'))
			$(this).outerHeight(0);
	});

	// sort the areas by number
	const radios = $('.kgl-selector span'),
		sortList = Array.prototype.sort.bind(radios);
	sortList((a, b) => {
		const aN = $(a).data('sort'),
			bN = $(b).data('sort');
		return aN < bN ? -1 : aN > bN ? 1 : 0;
	});
	$('.kgl-selector').append(radios);

	// collapse functionality
	$('.kgl-title').on('click', function() {
		if ($(this).hasClass('open')) return;
		const col = $(this).siblings('.kgl-collapse.open'),
			open = $(this).siblings('.open');
		col.outerHeight(0);
		open.removeClass('open');
		$(this).addClass('open');
		const next = $(this).next();
		next.addClass('open').outerHeight(next.data('height'));
	});

	// color popup names
	$('.kgl-color').on('mouseover', function() {
		$('.kgl-popup', this).addClass('pop');
	}).on('mouseleave', function() {
		$('.kgl-popup', this).removeClass('pop');
	});

	// color change
	$('.kgl-color').on('click', function() {
		const box = $(this).closest('.kgl-picker'),
			select = $('.kgl-selector', box);
		if (!$(':checked', select).length) return;
		const $el = $(':checked', select),
			area = $el.val(),
			wapf = $el.data('wapf'),
			color = $(this).data('color');
		$(`[data-field-id=${wapf}]`).val($(this).data('ral'))
		$(`[id=${area}]`).css({
			fill: color
		}).find('*').css({
			fill: color
		});
	});

	$('.kgl-option input').on('change', function() {
		const on = $(this).is(':checked'),
			id = $(this).data('id'),
			wapf = $(this).data('wapf');

		$(`#kgl-${id}, [for=kgl-${id}]`).attr('hidden', !on);
		$(`[id=${id}]`).attr('style', 'display: ' + (on ? 'initial' : 'none'));
		$(`input[data-field-id=${wapf}]`).prop('checked', on);

		let price = parseInt($('.kgl-price').data('base'));
		$('input[data-field-id]').each(function() {
			if ($(this).is(':checked'))
				price += parseInt($(this).data('wapf-price'));
		});
		$('.kgl-price').text(`£${price}`);
	});

	let formSuccess = false;
	$('#kgl-form').on('submit', function(e) {
		if (formSuccess) return;
		e.preventDefault();

		let error = false;
		$('input[id^=ral-color-]', this).each(function() {
			if (error) return;
			const option = $(`[data-id=${$(this).data('selector')}]`);
			if ((option.length && option.is(':checked') && !$(this).val()) || (!option.length && !$(this).val()))
				error = $(this).data('error');
		});

		if (error) {
			window.alert(error);
			return;
		}

		formSuccess = true;
		$(this).trigger('submit');
	})
});
