/*
  prospects page */

$(function(){




  // set default tab
  function settab(){
  $(".tab-pane").hide()
  $(".prospective").show()
  $(".domain-prospects-page li.active").removeClass("active")
  $(".domain-prospects-page #tab3").parents("li").addClass("active")
  }

  // add domains
  $(".add-domaintab form").submit(function(){
    dis.DS("add", {
      list: $(".add-domaintab textarea").val()
      ,url: $(".add-domaintab [name=url]").val()
      ,host: $(".add-domaintab [name=targethost]").val()
    }, function(ret){
      console.debug("successful",ret);
      if(ret && ret.inserted){
        alert(""+ret.inserted+" domain(s) stored");
        window.location.reload();
      }else alert("no domain was stored");
    });
    return false;
  });
  
  
  // add host company
  dis.DS("table=tblTargetCompany", {}, function(ret){
    var sel = $(".add-more select");
    ret.forEach(function(el){
      $("<option value="+el.ID+">"+el.Name+"</option>").appendTo(sel);
    });
  });
  
  $("[action=add-domain]").click(function(){
    var co = prompt("Enter the name of the new company:");
    if(co)dis.DS('', {'newCompany':co}, function(ret){
      if(ret)
        window.location.reload();
      else
        console.error("could not store new co:", ret);
    });
  }); 
  
  $("[action=remove-domain]").click(function(){
    if(!confirm("Do you really delete the selected host provider?"))return; 
    dis.DS('', {'delCompany':$("[name=targethost]").val()}, function(ret){
      if(ret){
        console.debug("deleted host",ret)
        window.location.reload();
      }else console.error("could not delete co:", ret);
    });
  }); 
  
  $(".forceProcessDomains").click(function(){
    if(!confirm("Would you like to force process domains? You have to take care about query limits."))return; 
    dis.DS('', {'forceProcessDomains':'I know what I am doing'}, function(ret){
      if(ret){
        console.debug("forceProcessDomains doone",ret)
        alert("Process initiated");
      }else console.error("could not delete co:", ret);
    });
  }); 
  

  // edit domain
  function editClk(E){
    var tr = $(this).parents("tr"), did=tr.attr("did"), rec=domains[did], Modal=$("#editmodal");
    for(var k in rec)Modal.find("[name="+k+"]").val(rec[k]);
    $("#editmodal .Domain").html(rec.Domain).attr("did", did);
    Modal.modal("show");
  }
  

  // save edited domain
  $("#editmodal .save").click(function(E){
    var did = $("#editmodal [did]").attr("did");
    DS("domainupdate="+did, {form: $("form").serializeArray()}, function(ret){
      if(!ret)return alert("Changes were not saved!");
      var tr = $("tr[did="+did+"]");
      console.debug(tr);
      $("#editmodal [name]").each(function(i,input){
        tr.find("."+$(input).attr("name")).html( $(input).val() );
      });
    });
  });
  

  var domains = [];
  
  
  // delete pending domain
  $(".pending-validation .delete").click(function(){
    var ids = [], ediTable = $(".pending-validation table.table");
    ediTable.bootstrapTable('getSelections').forEach(function(el){
      ids.push(el.ID);
    });
    if(!ids.length)return;
    if(!confirm("Are you sure to delete selected domain(s)?"))return;
    dis.DS("domaindelete="+ids.join(","), {}, function(ret){
      console.debug(ret);
      var r;
      ediTable.bootstrapTable("remove", r={ field:"ID", values:ids });
      console.debug('deletePending',r);
    });
  });


  // discard prospective domain
  $(".prospective .delete a").click(function(){
    var ids = [], ediTable = $(".prospective table.table");
    ediTable.bootstrapTable('getSelections').forEach(function(el){
      ids.push(el.ID);
      console.debug(el);
    });
    if(!ids.length)return;
    console.debug(ids.join(","));
    if(!confirm("Are you sure to discard selected domain(s)?"))return;
    dis.DS('', {discardProspective:ids.join(",")}, function(ret){
      console.debug(ret);
      var r;
      ediTable.bootstrapTable("remove", r={ field:"ID", values:ids });
      console.debug('discardProspective',r);
    });
  });

  

  //* templates {
  $(".editample [data-label]").attr("contenteditable",true);

  
  // edit
  function editclick(el){
    var a=$(el.currentTarget), i = $(el.target), div=a.parents("[tplid]");

    if( i.is(".fa-save")){
      // we have a save botton clicked
      i.removeClass("fa-save").addClass("fa-pencil-square-o");
      dis.DS("saveTpl", {
        name: div.find("[data-label=name]").html()
        ,subject: div.find("[data-label=subject]").html()
        ,body: div.find("[data-label=content]").html()
        ,tplsave: div.attr("tplid")
      },function(ret){
        if(ret){
          $("[contenteditable]").attr("contenteditable",false);
          div.attr("tplid",ret)
        }
        else alert("Could not save TEmplate");
      })
    }else{
      // the button is Edit
      $("[contenteditable]").attr("contenteditable",true)
      i.addClass("fa-save").removeClass("fa-pencil-square-o");
    }
  }

  $(".template-list a.edit").click(editclick)

  // delete

  function deleteClick(el){
    
    if(confirm("Are you sure to delete template?")){
      var div = $(this).parents("[tplid]"), tplid=div.attr("tplid");
      console.debug("del click",tplid)
      dis.DS("",{deltpl:tplid},function(ret){
        if(ret){
          console.debug("template",tplid,"deleted")
          div.remove();
        }else alert("Could not delete template");
      })
    }
    return false;
  }

  // add new
  $("a.add-template").click(function(){
    $($("template.email").html()
      ).attr("tplid",-1
      ).prependTo(".template-list"
      ).find("a.edit").click(editclick)
    
  })

  // load the existing ones
  dis.DS("tpls",{},function(tpls){
    var pickTemplate = $("#pick-template")
    tpls.forEach(function(tpl){
      var div = $($("template.email").html().replace("_ID_",tpl.ID)
        ).attr("tplid",tpl.ID
        ).prependTo(".template-list"
        )
        ;
      div.find("form.dropzone").attr("id","dropattpl-"+tpl.ID)
      div.find("[data-label=name]").html(tpl.name);
      div.find("[data-label=subject]").html(tpl.subject);
      div.find("[data-label=content]").html(tpl.body);
      div.find("a.edit").click(editclick);
      div.find("a.delete").click(deleteClick);
      
      if(tpl.atts)tpl.atts.forEach(function(att){
        var attdiv = $( $("template.tplatt").html().replace("_NAME_",att.name)
          ).attr("attid", att.ID).appendTo(div)
        
        attdiv.click(function(){
          if(!confirm("are you sure to delete attachment "+att.name+"?"))return true;
          dis.DS("delattach="+att.ID,{},function(ret){
            if(ret)attdiv.remove()
          })
        })
      })

      $("<option value="+tpl.ID+">"+tpl.name+"</option>").appendTo(pickTemplate)

    })
  })


  // trigger
  $(".emailTrigger").click(function(){
    var tplid = $("#pick-template").val()
      ,ediTable = $(".prospective table.table")
      ,ids=[]
      ,me = $(this)
      ,modal = me.parents(".modal"), modalbody=modal.find(".modal-body")
      ;


    ediTable.bootstrapTable('getSelections').forEach(function(el){
      ids.push(el.ID);
    });


    if( tplid && ids.length){
      dis.alertag("info","Please wait while sending "+ids.length+" emails...", modalbody)
      modal.find("button").attr("disabled","disabled")
      me.attr("content-orig",me.html() ).html("Please wait...")
      // start campaign
      dis.DS("emailTrigger",{tpl:tplid,ids:ids},function(ret){
        console.debug("emailTrigger",ret)
        modal.find("button").removeAttr("disabled")
        me.html(me.attr("content-orig"))
        var amsg, clas='info';

        if(ret.error){ 
          clas='danger';
          amsg=ret.error;
        } else if(!(ret.sent+ret.failed)){
          clas='warning';
          amsg = "No emails have been sent";
        } else if(!ret.sent){
          clas='danger';
          amsg="Failed to send "+ret.failed+" emails"
        } else if(!ret.failed){
          clas='success';
          amsg=ret.sent+" emails have been sent";
        }
        $(".prospective table.table").bootstrapTable("refresh",{silent:true});
        $("table.contacted").bootstrapTable("refresh",{silent:true});
        dis.alertag(clas, amsg, modalbody)
      })
    }
    
  })

  $("#SendEmailModal [data-dismiss]").click(function(){
    $(this).parents(".modal").find(".alert").remove()
  })

  // } templates

});
