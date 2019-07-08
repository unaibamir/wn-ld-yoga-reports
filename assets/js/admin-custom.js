jQuery(function( $ ){

  $('#date_from, #date_to').daterangepicker({
    showDropdowns: true,
    //maxYear: parseInt( moment().format('YYYY'), 10),
    autoUpdateInput: false,
    buttonClasses: "button button-secondary",
    applyButtonClasses: "button-primary"
  });

  $('#date_from, #date_to').on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
  });

  $('#date_from, #date_to').on('cancel.daterangepicker', function(ev, picker) {
      $(this).val('');
  });


  $( "#group_id" ).on( "change", function(){
    var group_id = $( this ).val();
    var data = {
      "group_id"  : group_id,
      "action"    : "woo_get_group_courses"
    };

    $.ajax({
        type: "POST",
        url: ajaxurl,
        dataType: "json",
        cache: false,
        data: data,
        success: function(response) {
            $( "#course_filter" ).empty();
            if( response.success ) {
                if( group_id != '' ) {
                    $( "#course_filter" ).append("<option value=''>Filter by Group Course</option>");
                } else {
                $( "#course_filter" ).append("<option value=''>Filter by Course</option>");
                }
                $.each( response.data, function( index, item ){
                    $( "#course_filter" ).append("<option value='"+item.course_id+"'>"+item.course_title+"</option>");
                });
            } else {
                $( "#course_filter" ).append("<option value=''>No Course Found</option>");
            }
        }
    });
  });


   $('form#woo-reports').on('submit', function(event) {
      var group_id    = $("#group_id").val();
      var course_id   = $("#course_filter").val();

      console.log(group_id, course_id);

      if( group_id != "" && course_id == "" ) {
        alert("Please choose the Course from dropdown");
        $("#course_filter").focus();
        return false;
      }

      if( course_id == "" ) {
        alert("Please choose the Group or Course from dropdown");
        $("#course_filter").focus();
        return false;
      }
      
      
  });

});