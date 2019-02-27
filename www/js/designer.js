           
            $('.selectall').click(function (event) {
                if (this.checked) {
                    // Iterate each checkbox
                    $(':checkbox').each(function () {
                        this.checked = true;
                        $(this).closest("tr").addClass("checked");
                    });
                }
                else {
                    $(':checkbox').each(function () {
                        this.checked = false;
                        $(this).closest("tr").removeClass("checked");
                    });
                }
            });
            $(document).ready(function () {
                $('table tbody input[type="checkbox"]').click(function () {
                    if ($(this).prop("checked") == true) {
                        $(this).closest("tr").addClass("checked");
                    }
                    else if ($(this).prop("checked") == false) {
                        $(this).closest("tr").removeClass("checked");
                    }
                });
            });
            $('table tbody input[type="checkbox"]').click(function () {
                $(".selectall").removeAttr('checked');
                $(".selectall").prop('checked', false);
            });
            
            function tabsClick(){
                $('input[type="checkbox"]').removeAttr('checked');
                $("table tbody tr").removeClass("checked");
            }           
            
