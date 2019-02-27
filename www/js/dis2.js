"use strict";

var dis = {};

$(function(){

  // column formatters
  dis.fmt = {
    domain: function(val, row, i){
      return '<a target="_blank" href="http://'+val+'/">'+val+'</a>';
    }
    
    ,date: function(val,row,i){
      return val;
      return $.datepicker.formatDate("dd-mm-yy", new Date(val) );
      return d;
    }
    
  };
  
  // add unprocessed domain
  $(".unproc .add").click(function(){
    $(".unproc .add").addClass("hidden");
    $(".unproc .textarea").removeClass("hidden");
  });
  
  function DS(path,data,callback){
    $.ajax({ url:"datasource.php?"+path
      ,data:data
      ,dataType: "JSON"
      ,success:callback
      ,cache:false
      ,method:"POST"
      ,error:function(E){
        console.error("DS error", E);
      }
    });
  }


  dis.alertag = function(categ, body, target){
    target.find(".alert").remove()
    if(categ)
    return $("<div class='alert alert-"+categ+"'>"+body+"</div>").appendTo(target)
  };

  
  // we're gonna use these features everywhere
  window.dis = dis;
  window.dis.DS = DS;

  // account
  var logina = $(".navbar-right a");


  resizeTableBody()
  
  
});


  // brute-force resizing of datatables
  function debouncer( func , timeout ) {
     var timeoutID , timeout = timeout || 200;
     return function () {
        var scope = this , args = arguments;
        clearTimeout( timeoutID );
        timeoutID = setTimeout( function () {
            func.apply( scope , Array.prototype.slice.call( args ) );
        } , timeout );
     }
  }

  function resizeTableBody(){
    $(".fixed-table-body").height( $(window).height()-345 ) 
  }

  
  $( window ).resize( debouncer( resizeTableBody) );


  



var ud = function() {

  // UI elements
  $('#domainAdd').on('shown.bs.modal', function () {
    $('#domainAdd input').focus()
  });
  
  
  $( "#tabs" ).tabs();	// tabs on page



  // domain extensions
  var extensionsTable = $('#domainextensions')
    .DataTable( {
      "ajax": "datasource.php?domextList",
      "columns": [
          { "data": "extension", "createdCell": function(td,cellData,rowData,row,col){
            $(td).attr("contenteditable","").on("blur",function(){
              $(".debug").load("datasource.php"
                ,{updateExt:rowData.extension,value:$(this).html()}
                ,function(){extensionsTable.ajax.reload();}
              );
            });
          }},
          { "data": "monitor_frequency", "createdCell":function(td,cellData,rowData,row,col){
            $(td).attr("contenteditable","").attr("align","center").on("blur",function(){
              $(".debug").load("datasource.php"
                ,{updateDays:rowData.extension,value:$(this).html()}
                ,function(){extensionsTable.ajax.reload();}
              );
            });
          }},
          { "data": "extension", "createdCell": function (td, cellData, rowData, row, col) {
            var btn = $("<button>delete</button>");
            $(btn).on("click",function(){
              if(confirm("Really delete domain extension "+rowData.extension+"?"))
                $(".debug").load("datasource.php"
                  ,{deleteExt:rowData.extension}
                  ,function(){extensionsTable.ajax.reload();}
                );
            });
            $(td).html("").append(btn);
          }}
      ]
  });
  
  
  // add new domain extension
  $("button.addExtension").on("click",function(){
    $(".debug").load("datasource.php"
      ,{addNewExt:$("#newExtension").val(), days:$("#newExtfreq").val()}
      ,function(){extensionsTable.ajax.reload();}
    );
  });


  function click_dummy(){
    alert("not ready yet");
  }
  
  
  // save domain settings
  $("#settings button.save").click(function(){
    $.getJSON("datasource.php", {saveSettings:$("#domainID").val(),di:$("#domainInterval").val(),si:$("#screenshotInterval").val()}
      ,function(ret){
      
      if(ret)domainsTable.ajax.reload();
        else alert("Error saving settings.");
    });
    
  });
  

  // domain list
  var domainsTable = $('#domainlist')
    .DataTable( {
      "ajax": "datasource.php?domainList",
      "columns": [
          { "data": "domain" },
          { "data": "freq" },
          { "data": "checked" },
          { "data": "changed.updated" },
          { "data": "expiry" },
          { "data": "screenshot" },
          { "data": "id", "createdCell": function (td, cellData, rowData, row, col) {
            $(td).parent().attr("domainID", rowData.id);

            // details button
            var btnDet = $(" <button class=view>View</button>");
            $(btnDet).on("click",function(){
              console.debug("detklik", rowData.domain);
              $("#details").modal("show");
              $("#details .modal-body").load("details.php?domain="+rowData.domain);
              
            });
            

            // edit button
            var btnEdit = $(" <button class=edit>Edit</button>");
            //*
            $(btnEdit).on("click",function(){
              var st=$("#settings");
              st.modal("show");
              $.getJSON("datasource.php?loadSettings="+rowData.id,function(ret){
                $("#domainName").html(ret.domain);
                $("#domainInterval").val(ret.checkDomainDays);
                $("#screenshotInterval").val(ret.screenshotDays);
                $("#domainID").val(ret.id);
              });
              
            });
            //*/

            // delete button
            var btnDel = $("<button class=delete>Delete</button>");
            $(btnDel).on("click",function(){
              if(confirm("Really delete domain "+rowData.domain+" and all of its recorded data?"))
                $(".debug").load("datasource.php"
                  ,{deleteDomain:rowData.id}
                  ,function(){domainsTable.ajax.reload();}
                );
            });

            $(td).html("").attr("align","center").append(btnDet).append(btnEdit).append(btnDel);
          }}
      ]
  });
  
  
  $("#popup button.close").on("click",function(){
    $("#popup").hide();
    $("#popup .content").html("");
  });
  
  
  function modal_alert(msg){
    $("<div class='alert alert-dismissable alert-danger'>"+msg+"</div>").appendTo("#domainAdd .alerts");
  }

  // add new domain
  $("button.addDomain").on("click",function(){
    $.getJSON("datasource.php"
      ,{addDomain:$("#newDomain").val() }
      ,function(ret){
        console.debug(ret);
        if(ret){
          domainsTable.ajax.reload();
          $("#domainAdd").modal("hide");
          $("#newDomain").val("");
        }else{
          modal_alert("Could not add that domain. It may already exist.");
        }
      }
    );
  });
  
  
  
  // last runs
  $("#lastrun").DataTable({
      "ajax": "datasource.php?lastrun",
      "order": [[0,"desc"]],
      "columns": [
          { "data": "date" },
          { "data": "domain" },
          { "data": "changes" }
      ]
  });
  
};
