
M.report_filesize = {
    Y : null,
    transaction : [],

	init: function (Y) {
        var select = Y.one('#menucategory');
        select.on('change', function (e) {
        	var id = e.target.get('value');
        	window.location = M.cfg.wwwroot + "/report/filesize/index.php?category=" + id
        });
    }
}