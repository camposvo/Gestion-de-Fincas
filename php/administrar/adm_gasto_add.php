<?php 
	session_start();
	include_once ("adm_utilidad.php");
	$usu_autentico= isset($_SESSION['autentificado'])?$_SESSION['autentificado']:'';
	if ($usu_autentico != "SI") {
		session_destroy();
    	echo"<script language='JavaScript' type='text/JavaScript'>top.location.href='../../html/fin_sesion.html'</script>";
		exit();
	}
	
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>La Peperana</title>

		<link rel="stylesheet" href="../../assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="../../assets/font-awesome/4.2.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="../../assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="../../assets/css/datepicker.min.css" />
		<link rel="stylesheet" href="../../assets/fonts/fonts.googleapis.com.css" />
		<link rel="stylesheet" href="../../assets/css/chosen.min.css" />
		<link rel="stylesheet" href="../../assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
		<script src="../../assets/js/ace-extra.min.js"></script>		
				
</head>
<body>
<?php 
/*-------------------------------------------------------------------------------------------
	RUTINA: Se utiliza para recibir las variables por la url.
-------------------------------------------------------------------------------------------*/
	$o_cantidad  = 0;
	$o_cantidad2 = 0;
	$mostrar_rs = false;
	
	if (!$_GET)	{
		foreach($_POST as $nombre_campo => $valor){
			$asignacion = "\$" . $nombre_campo . "='" . $valor . "';";
			eval($asignacion);
		}
		$modo = isset($_POST['modo'])?$_POST['modo']:'Insertar Nuevo Registro';
	}else{
		foreach($_GET as $nombre_campo => $valor){
			$asignacion = "\$" . $nombre_campo . "='" . $valor . "';";
			eval($asignacion);
		}
		$modo = isset($_GET['modo'])?$_GET['modo']:'Insertar Nuevo Registro';
	}
	
	//var_dump($_POST['det_Proyecto']);
	//var_dump($_POST['det_Precio']);
	
	if ($_POST['det_Proyecto'])	{ // Recibe el detalle de la Factura
		$a_proyecto	= $_POST['det_Proyecto'];
		$a_precio 	= $_POST['det_Precio'];
		$a_articulo	= $_POST['det_Articulo'];
		$a_cantidad	= $_POST['det_Cantidad'];
		$a_almacen	= $_POST['det_CheckAlmacen'];
	}
	
	
	
	$obj_miconexion = fun_crear_objeto_conexion();
	$li_id_conex = fun_conexion($obj_miconexion);
	
	$co_usuario     	= 	$_SESSION["li_cod_usuario"];
	$arr_proveedor  	=  	Combo_Proveedor();
	$arr_rubro      	=  	Combo_Rubro();
	$arr_articulo 		=  	Combo_Articulo();
	$arr_almacen       	=  	Combo_Almacen();
	$x_fecha_actual    	=  	date('Y/m/d');
	
/*-------------------------------------------------------------------------------------------
	AGREGA UN GASTO
-------------------------------------------------------------------------------------------*/	
	if ($tarea == "I"){
		$error_sql = false;
		$o_total = number_format($o_total,2,".","");
		$o_precio = number_format($o_precio,2,".","");
		$x_cant_item= $x_cant_item==''?0:$x_cant_item;
		
		$ls_sql = "INSERT INTO t20_factura(
			fk_responsable, 
			fe_fecha_factura, 
			fe_fecha_registro, 
            tx_factura, 
			tx_tipo, 
			fk_proveedor, 
			tx_concepto,
            nu_total		
			)
		VALUES (
			$co_usuario, 
			'$o_fecha', 
			now(), 
			'S/N', 
			'GASTO',	
			$o_proveedor,
			'$x_referencia',
			$x_total
		);";
						
		//echo $ls_sql;
						
		if($obj_miconexion->fun_consult("BEGIN TRANSACTION; ".$ls_sql) == 0){
			$error_sql = true;
		}else{
			
			$ls_sql ="SELECT CURRVAL('t20s_pk_factura')	"; //Obtiene el ID de la factura
			$ls_resultado =  $obj_miconexion->fun_consult($ls_sql);
			if($ls_resultado != 0){
				$row = pg_fetch_row($ls_resultado,0);
				$id_factura       = $row[0];			
			}else{
				fun_error(1,$li_id_conex,$ls_sql,$_SERVER[PHP_SELF], __LINE__);
			}			

			foreach($a_precio as $k=>$valor){
				
				if( !empty( $a_almacen) && is_array( $a_almacen) ){
				   // Si la variable está definida, no vacía y es un array se ejecuta esto.
				   $chk_almacen = in_array($k, $a_almacen)?'on':'off';
				} else{
					 $chk_almacen = 'off';
				} 
								
				$ls_sql = "INSERT INTO t01_detalle(
					fk_responsable, 
					  fk_factura, 
					  fk_rubro, 
					  fk_articulo,
					  nu_cantidad, 
					  nu_precio, 
					  tx_unidad, 
					  fe_fecha_registro,
					  in_inventario		
					)
				VALUES (
					$co_usuario, 
					$id_factura,
					$a_proyecto[$k],
					$a_articulo[$k],
					$a_cantidad[$k],
					$a_precio[$k],
					'UND',
					now(), 
					'$chk_almacen'					
					);";								
				//echo $ls_sql;
								
				if($obj_miconexion->fun_consult($ls_sql)){
					
				}else{
					$error_sql = true;					
				}	
			}	
			
			if($error_sql){
			  $ls_sql_FIN = " ROLLBACK; ";
			  $msg = "¡Error en la Consulta !";
			  
			}else{
				 $ls_sql_FIN = " COMMIT; ";
				 $msg = "¡Factura Agregada Satisfactoriamente !";                     
			}

			$ls_resultado =  $obj_miconexion->fun_consult($ls_sql_FIN);
			$parametros = "tarea=A";
			echo "<script language='javascript' type='text/javascript'>alert('$msg');location.href='adm_gasto_add.php?$parametros';</script>";
			//
		}
		
		//NOTA: Este insert/update  genera un trigger a nivel de base de datos que almacena el pago del gasto en la tabla abono
		
	}

/*-------------------------------------------------------------------------------------------
	ACTUALIZA UN GASTO
-------------------------------------------------------------------------------------------*/
	if ($tarea == "U"){
		$error_sql = false;
		$id_factura = $x_movimiento;
		
		$ls_sql ="UPDATE t20_factura SET 
			fk_responsable    	= $co_usuario,
			fe_fecha_registro 	=now(),
			tx_factura        	='$o_factura', 
			tx_tipo             ='GASTO', 
			fk_proveedor        = $o_proveedor,
			fe_fecha_factura  	='$o_fecha',
			tx_concepto         ='$x_referencia',
			nu_total		  	= $x_total	
		WHERE pk_factura   	  	= $id_factura;";

								
		if($obj_miconexion->fun_consult("BEGIN TRANSACTION; ".$ls_sql) == 0){
			fun_error(1,$li_id_conex,$ls_sql,$_SERVER[PHP_SELF], __LINE__);
		}else{
			
			$ls_sql ="DELETE FROM t01_detalle WHERE fk_factura = $id_factura; "; //
			$ls_resultado =  $obj_miconexion->fun_consult($ls_sql);
			if($ls_resultado){
			
			}else{
				fun_error(1,$li_id_conex,$ls_sql,$_SERVER[PHP_SELF], __LINE__);
			}			
			
			foreach($a_precio as $k=>$valor){
				
				if( !empty( $a_almacen) && is_array( $a_almacen) ){
				   // Si la variable está definida, no vacía y es un array se ejecuta esto.
				   $chk_almacen = in_array($k, $a_almacen)?'on':'off';
				} else{
					 $chk_almacen = 'off';
				} 
				
				
					
				$ls_sql = "INSERT INTO t01_detalle(
					  fk_factura, 
					  fk_rubro, 
					  fk_articulo,
					  nu_cantidad, 
					  nu_precio, 
					  tx_unidad, 
					  fe_fecha_registro,
					  in_inventario		
					)
				VALUES (
					$id_factura,
					$a_proyecto[$k],
					$a_articulo[$k],					
					$a_cantidad[$k],
					$a_precio[$k],
					'UND',
					now(), 
					'$chk_almacen'								
					);";								
				//echo $ls_sql;
								
				if($obj_miconexion->fun_consult($ls_sql)){
					
				}else{
					$error_sql = true;
				}	
			}	
			
			if($error_sql){
			  $ls_sql_FIN = " ROLLBACK; ";
			  $msg = "¡Error en la Consulta !";
			  
			}else{
				 $ls_sql_FIN = " COMMIT; ";
				 $msg = "¡Factura Actualizada Satisfactoriamente !";                     
			}

			$ls_resultado =  $obj_miconexion->fun_consult($ls_sql_FIN);
			
			
			echo "<script language='javascript' type='text/javascript'>alert('$msg'); location.href='adm_gasto_view.php'</script>";
		}

		$tarea = 'M';
	}
	
/*-------------------------------------------------------------------------------------------
	LEE DATOS DE UN GASTO
-------------------------------------------------------------------------------------------*/
	if ($tarea == "M"){		
		$ls_sql ="SELECT pk_factura, fk_responsable, fk_proveedor, to_char(fe_fecha_factura, 'dd/mm/yyyy'),  tx_factura,
					nu_total, nu_subtotal, nu_abono, tx_concepto
					FROM t20_factura
					WHERE pk_factura = $x_movimiento";
		
		$ls_resultado =  $obj_miconexion->fun_consult($ls_sql);
		if($ls_resultado != 0){
			$row = pg_fetch_row($ls_resultado,0);
			$id_factura      = $row[0];
			$co_usuario	     = $row[1];
			$o_proveedor  	 = $row[2];	
			$o_fecha         = $row[3];
			$o_factura      = $row[4];
			$x_total        = $row[5];
			$x_subtotal     = $row[6];
			$x_abono    	= $row[7];
			$x_referencia     = $row[8];
			
			// Extrae el detalle de la factura
			$ls_sql ="SELECT fk_rubro, fk_articulo, nu_cantidad, nu_precio,  
				  nu_cantidad * nu_precio as total, in_inventario
				  FROM t01_detalle
				  WHERE fk_factura = $id_factura ;";
			//echo $ls_sql;
			
			$ls_resultado =  $obj_miconexion->fun_consult($ls_sql);
			if($ls_resultado){
				$mostrar_rs = true;
				// Consulta exitosa					
			}else{
				fun_error(1,$li_id_conex,$ls_sql,$_SERVER[PHP_SELF], __LINE__);
			}
			
		}else{
			fun_error(1,$li_id_conex,$ls_sql,$_SERVER[PHP_SELF], __LINE__);// enviar mensaje de error de consulta
		}
		$tarea = 'U';
		$modo= 'Actualizar Datos';
	}
	
	/*Se prepara para Insertar un Registro*/
	
	if ($tarea == "A"){
		$tarea = 'I';
		$modo= 'Ingresar Nuevo Gasto';
	}

	$x_fecha_registro = date('d/m/Y H:i');

?>

<!-- Content Header (Page header) -->

<div class="container-fluid">
			<div class="page-header">
				<h1><?php echo $modo;?>	</h1>
			</div><!-- /.page-header -->
			
			<div class="row"><!-- ROW CONTENT BEGINS -->
				<div class="col-xs-12">
				
				<form class="form-horizontal" name="formulario">				
					<div class="row">
						<div class="col-xs-12 col-sm-12 widget-container-col">
							<div class="widget-box ">
								<div class="widget-body">
									<div class="widget-main">
																					
											<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" for="id-date-picker-1" >Fecha Fact.</label>
												<div class="col-sm-4" >	
													<div class="input-group">
														<input name="o_fecha" value="<?php echo $o_fecha;?>" class="col-xs-10 col-sm-6 form-control date-picker" id="id-date-picker-1" type="text" data-date-format="dd/mm/yyyy" readonly />
														<span class="input-group-addon">
															<i class="fa fa-calendar bigger-110"></i>
														</span>
													</div>
												</div>
											</div>	
												
										<!-- 	<div class="form-group">
												<label  class="col-sm-3 control-label no-padding-right"  for="o_factura">Factura</label>
												<div class="col-sm-7" >
													<input class="col-xs-10 col-sm-7" name="o_factura" value="<?php //echo $o_factura;?>" id="o_factura" type="text"  placeholder="Ingrese Nro. de Factura">
												</div>
											</div> -->
											
											<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" >Proveedor</label>
												<div class="col-sm-7" >	
													<select type="select-one" name="o_proveedor" class="col-xs-10 col-sm-7 chosen-select " id="" data-placeholder="Selecciona un Proveedor...">
														<?php
															if ($o_proveedor == ""){
																echo "<option value='0' selected></option>";
															}else{
																echo "<option value='0'></option>";
															}
															foreach($arr_proveedor as $k => $v) {
																$ls_cadenasel =($k == $o_proveedor)?'selected':'';
																echo "<option value='$k' $ls_cadenasel>$v</option>";                
															}
														?>									
													</select>
												</div>													
											</div>
											
											<div class="form-group">
												<label  class="col-sm-3 control-label no-padding-right"  for="x_referencia">Ref</label>
												<div class="col-sm-7" >
													<input class="col-xs-10 col-sm-7" name="x_referencia" value="<?php echo $x_referencia;?>" id="x_referencia" type="text"  placeholder="Referencia" maxlength="30">
												</div>
											</div>
																		
																						
										</div>	
									</div>
								</div>
						</div>
					</div>
					
					<div class="space-4"></div>
					
					<div class="row">
						<div class="col-xs-12 col-sm-12 ">
							<button type="button" onClick="nuevoArticulo();"  class="btn-success btn-xs pull-left ">
								<i class="ace-icon fa fa-plus align-top bigger-100 "></i>
								Add
							</button>	
						</div> 
					</div>
					
					<div class="space-4"></div>
		
					<div class="row">
						<div class="col-xs-12 col-sm-12">
							<table id="simple-table" class="table table-striped table-bordered table-hover">
								<thead>
									<tr class="bg-primary" >
										<th>Proyecto</th>
										<th>Articulo</th>
										<th>Cantidad</th>
										<th>Precio</th>
										<th>SubTotal</th>
										<th>Almacen</th>
										<th>Eliminar</th>
									</tr>
								</thead>
								<tbody id="tblDetalle">	
								<?php
									if($mostrar_rs){
										$li_numcampo = $obj_miconexion->fun_numcampos()-6; // Columnas que se muestran en la Tabla
										$li_indicecampo = $obj_miconexion->fun_numcampos()-1; // Referencia al indice de la columna clave
										fun_dibujar_tabla($obj_miconexion,$li_numcampo,$li_indicecampo, 'LISTAR_GASTO_ADD', 0); // Dibuja la Tabla de Datos
										$obj_miconexion->fun_closepg($li_id_conex,$ls_resultado);										
									}
								?>
								</tbody>
							</table>
						</div> 
					</div>
								
					<div class="space-4"></div>
					
					<div class="row">
						
						<div class="col-xs-12 col-sm-8 ">
								<button type="button" onClick="Atras('<?php echo "tarea=B"; ?>')" class="btn-sm  btn-danger">
									<i class="ace-icon fa fa-reply  bigger-110 icon-on-right"></i>
									Regresar
								</button>
								
								<button type="button" onClick="Guardar('<?php echo $tarea;?>');" class="btn-sm btn-success">
									<i class="ace-icon fa fa-check  icon-on-right bigger-110"></i>
									Guardar
								</button>																								

						</div>							
												
						<div class="col-xs-12 col-sm-4 widget-container-col">
							<div class="widget-box ">
								<div class="widget-body">
									<div class="widget-main ">
																		
										<div class="form-group">
											<label  class="col-sm-3 control-label no-padding-right">Total</label>
											<div class="col-sm-7" >
												<input readonly = "readonly" class="col-xs-10 col-sm-7" name="x_total" value="<?php echo $x_total;?>" id="x_total" type="text"  >
											</div>
										</div>
								
									</div>	
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="x_movimiento" value="<?php echo $x_movimiento;?>">
					<input type="hidden" name="tarea" value="<?php echo $tarea;?>">
					<input type="hidden" name="modo" value="<?php echo $modo;?>"> 
					<input type="hidden" name="x_vendedor" value="<?php echo $x_vendedor;?>">
					<input type="hidden" name="x_cliente" value="<?php echo $x_cliente;?>">   
					<input type="hidden" name="x_factura" value="<?php echo $x_factura;?>">
					<input type="hidden" name="x_proyecto" value="<?php echo $x_proyecto;?>">
					<input type="hidden" name="x_fecha_ini" value="<?php echo $x_fecha_ini;?>">
					<input type="hidden" name="x_fecha_fin" value="<?php echo $x_fecha_fin;?>">
					<input type="hidden" id = "input_filtro" name="input_filtro" 		 value="<?php echo $input_filtro;?>">		
					<input type="hidden" name="check" value="<?php echo $check;?>">			
				</form>
			</div> <!-- /.row tabla principal -->
		</div> <!-- /.page-content -->

</body>

<!--  SISTEMA   -->	
	<script src="../../js/funciones.js"></script>  
	<script src="../../assets/js/jquery.2.1.1.min.js"></script>
	<script src="../../assets/js/bootstrap.min.js"></script>
	<script src="../../assets/js/jquery-ui.custom.min.js"></script>
	<script src="../../assets/js/bootstrap-datepicker.min.js"></script>
	<script src="../../assets/js/ace-elements.min.js"></script>
	<script src="../../assets/js/ace.min.js"></script>
	<script src="../../assets/js/chosen.jquery.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
	
		
		$('.date-picker').datepicker({
			autoclose: true,
			todayHighlight: true
		})
		//show datepicker when clicking on the icon
		.next().on(ace.click_event, function(){
			$(this).prev().focus();
		});
		
		if(!ace.vars['touch']) {
			$('.chosen-select').chosen({allow_single_deselect:true}); 
		
			//resize chosen on sidebar collapse/expand
			
			$(document).on('settings.ace.chosen', function(e, event_name, event_val) {
				if(event_name != 'sidebar_collapsed') return;
				$('.chosen-select').each(function() {
					 var $this = $(this);
					 $this.next().css({'width': $this.parent().width()});
				})
			});	
	
			$('#chosen-multiple-style .btn').on('click', function(e){
				var target = $(this).find('input[type=radio]');
				var which = parseInt(target.val());
				if(which == 2) $('#form-field-select-4').addClass('tag-input-style');
				 else $('#form-field-select-4').removeClass('tag-input-style');
			});
		}
		
		window.parent.ScrollToTop(); // invoca la funcion ScrollToTop que se encuentra en interace.php para posicionar el scroll vertical
		
	} );	
	

</script>

			

<script>
// Se invoka en tiempo de ejecucion para activar la clase select multiple
	function chosen(){
		
		if(!ace.vars['touch']) {
			$('.chosen-select').chosen({allow_single_deselect:true}); 
		
			//resize chosen on sidebar collapse/expand
			
			$(document).on('settings.ace.chosen', function(e, event_name, event_val) {
				if(event_name != 'sidebar_collapsed') return;
				$('.chosen-select').each(function() {
					 var $this = $(this);
					 $this.next().css({'width': $this.parent().width()});
				})
			});	
	
			$('#chosen-multiple-style .btn').on('click', function(e){
				var target = $(this).find('input[type=radio]');
				var which = parseInt(target.val());
				if(which == 2) $('#form-field-select-4').addClass('tag-input-style');
				 else $('#form-field-select-4').removeClass('tag-input-style');
			});
		}
		
	}
	
	    
// crea un campo dinamicamente
	function crearCampo(nombre,readonly, evento){
		td= document.createElement('td');
		txt = document.createElement('input');
		txt.type = 'text';
		txt.setAttribute('name',nombre);
		txt.setAttribute('size',7);
		txt.setAttribute('onkeyup',evento); 
		txt.setAttribute('onkeypress','return validardec(event);');
		
		if(readonly){
			txt.setAttribute('readonly','readonly')
		}
		td.appendChild(txt);
		return td;		
	}	
		
// crea un campo dinamicamente
	function calcular(){		
		cantidad = document.getElementsByName('det_Cantidad[]');
		precios =  document.getElementsByName('det_Precio[]');
		subtotal = document.getElementsByName('det_Subtotal[]');
		
		var_subtotal =0;
		for(x=0; x < cantidad.length; x++){
			sub = redondeo2decimales(cantidad[x].value * precios[x].value);
			subtotal[x].value = sub;
			var_subtotal += sub;			
		}
		document.getElementById('x_total').value = redondeo2decimales(var_subtotal);		
	}	
	
		
//crea dinamicamente un fila para un nuevo articulo
	function nuevoArticulo(){
		var proy = <?php echo json_encode($arr_rubro); ?>;
		var articulo = <?php echo json_encode($arr_articulo); ?>;
		
		
		//var yea=document.getElementById("tblDetalle").rows.length;
		//var yea=document.getElementById("tblDetalle").rowIndex; 
		
		// Mostramos los valores del array

		destino = document.getElementById('tblDetalle');
		tr = document.createElement('tr');
			
	
		
		// Select Proyecto
		td = document.createElement('td');
		sele = document.createElement('select');
		sele.name = 'det_Proyecto[]';
		sele.setAttribute("class","chosen-select");

		opt = document.createElement('option');
		opt.value = '0';
		opt.setAttribute('data-placeholder','Selecciona un Proveedor');
		sele.appendChild(opt);

		for (var p in proy){
			opt = document.createElement('option');
			opt.value = p;
			opt.innerHTML = proy[p];
			sele.appendChild(opt);
		}		
		td.appendChild(sele);
		tr.appendChild(td);
				
		// Select Articulo
		td = document.createElement('td');
		sele = document.createElement('select');
		sele.name = 'det_Articulo[]';
		sele.setAttribute("class","chosen-select");

		opt = document.createElement('option');
		opt.value = '0';
		opt.setAttribute('data-placeholder','Selecciona un Proveedor');
		sele.appendChild(opt);

		for (var p in articulo){
			opt = document.createElement('option');
			opt.value = p;
			opt.innerHTML = articulo[p];
			sele.appendChild(opt);
		}		
		td.appendChild(sele);
		tr.appendChild(td);
		
		// Otros Campos
		tr.appendChild(crearCampo('det_Cantidad[]',false,'calcular()'));		
		tr.appendChild(crearCampo('det_Precio[]',false,'calcular()'));
		tr.appendChild(crearCampo('det_Subtotal[]',true,''));
		
	
		
		// Crea Check list para Inventarear
		td = document.createElement('td');
		x = document.createElement('input');
		x.type = 'checkbox';
		x.name = "det_CheckAlmacen[]";
		x.value = '';
		td.appendChild(x);
		tr.appendChild(td);
		
		
			// Crea Boton eliminar
		td = document.createElement('td');
		x = document.createElement('button');
		x.type = 'button';
		x.innerHTML = 'X';
		x.setAttribute('onClick','eliminarfila(this)');
		td.appendChild(x);
		tr.appendChild(td);
		
		destino.appendChild(tr);
			
		
		//LLama el JS que se requiere para el Select Multiple
		chosen();
		
	}	
		
	function eliminarfila(btn){
		fila = btn.parentNode.parentNode;
		fila.parentNode.removeChild(fila);
		calcular();
	}	
		
	function Guardar(Identificador){		
		if (document.getElementById('x_total').value > 0){ //Hay elementos en el detalle
			if(campos_blancos(document.formulario) == false){
				if (confirm('Esta conforme con los Datos Ingresados?') == true){
					
					// Permite asignar un valor el checkbox para identificar el que esta activo
					cantidad = document.getElementsByName('det_CheckAlmacen[]');					
					for(x=0; x < cantidad.length; x++){
						cantidad[x].value = x;
					}
					
					document.formulario.tarea.value = Identificador;
					document.formulario.action = "adm_gasto_add.php";
					document.formulario.method = "POST";
					document.formulario.submit();
				}
			}
		}else{
			alert("Debe cargar los Articulos")
		}
	}	    

	function Atras(parametros){
		document.formulario.tarea.value = "X";
		document.formulario.action = "adm_gasto_view.php";
		document.formulario.method = "POST";
		document.formulario.submit();
	}	
	
</script>

</html>