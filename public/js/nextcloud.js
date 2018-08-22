$(document).ready(function () {

			NextCloud._initFields();

			$("#studiensemester, #studiengang_kz").change(
				function()
				{
					NextCloud._initFields();
				}
			);

			$("#ausbildungssemester").change(
				function()
				{
					NextCloud._showLvs();
				}
			)
});

var NextCloud = {
	/*------------------------------------------------ AJAX CALLS -------------------------------------------------------*/
	getLehrveranstaltungGroupStrings: function (studiensemester, ausbildungssemester, studiengang_kz)
	{
		FHC_AjaxClient.ajaxCallPost(
			FHC_JS_DATA_STORAGE_OBJECT.called_path + "/getLehrveranstaltungGroupStrings",
			{
				"studiensemester": studiensemester,
				"ausbildungssemester": ausbildungssemester,
				"studiengang_kz": studiengang_kz
			},
			{
				successCallback: function(data, textStatus, jqXHR)
				{
					if (!FHC_AjaxClient.hasData(data))
						return;

					$.each(data.retval, function (i, lv) {
						$('#lvids').append($('<option>', {
							value: lv.lehrveranstaltung_id,
							text : lv.lvgroupname
						}));
					});
				}
			}
		);
	},

	getAusbildungssemesterByStudiensemesterAndStudiengang: function (studiensemester, studiengang_kz)
	{
		FHC_AjaxClient.ajaxCallPost(
			FHC_JS_DATA_STORAGE_OBJECT.called_path + "/getAusbildungssemesterByStudiensemesterAndStudiengang",
			{
				"studiensemester": studiensemester,
				"studiengang_kz": studiengang_kz
			},
			{
				successCallback: function(data, textStatus, jqXHR)
				{
					if (!FHC_AjaxClient.hasData(data))
						return;

					$("#ausbildungssemester").html("<option value='alle'>alle</option>");

					$.each(data.retval, function (i, sem) {
						$('#ausbildungssemester').append($('<option>', {
							value: sem.semester,
							text : sem.semester
						}));
					});
					NextCloud._showLvs();
				}
			}
		);
	},

	/*------------------------------------------------ "PRIVATE" METHODS ------------------------------------------------*/
	/**
	 * Initializes all input fields, fills them with values
	 * @private
	 */
	_initFields: function()
	{
		var studiensemester = $("#studiensemester").val();
		var studiengang_kz = $("#studiengang_kz").val();

		NextCloud.getAusbildungssemesterByStudiensemesterAndStudiengang(studiensemester, studiengang_kz);
	},

	/**
	 * Shows lvs in multiselect field depending on values of other fields
	 * @private
	 */
	_showLvs: function()
	{
		var studiensemester = $("#studiensemester").val();
		var studiengang_kz = $("#studiengang_kz").val();
		var ausbildungssemester = $("#ausbildungssemester").val();

		$("#lvids").empty();
		NextCloud.getLehrveranstaltungGroupStrings(studiensemester, ausbildungssemester, studiengang_kz);
	}

};
