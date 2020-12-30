$(function () {
  jQuery('.insert_row_input').on('change blur', function () {
    var tr = $(this).closest('tr'),
      checkbox = tr.find('.nullcheckbox');

    if ($(this).val() !== '') {
      checkbox.prop('checked', false);
    }
  });
  jQuery('.btn_back').on('click', () => {
    window.history && window.history.back();
  });
  jQuery('input[type=submit]').on('mouseover', function () {
    jQuery('.insert_row_input').each(function () {
      const tr = $(this).closest('tr'),
        checkbox = tr.find('.nullcheckbox');

      if ($(this).val() !== '') {
        checkbox.prop('checked', false);
      }
    });
  });
});
