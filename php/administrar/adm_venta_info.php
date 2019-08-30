<?php 
/*-------------------------------------------------------------------------------------------
	VERIFICACION Y AUTENTIFICACIN DE USUARIO. 
--------------------------------------------------------------------------------------------*/
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
	<link rel="stylesheet" href="../../assets/fonts/fonts.googleapis.com.css" />
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
	if (!$_GET)	{
		foreach($_POST as $nombre_campo => $valor){
			$asignacion = "\$" . $nombre_campo . "='" . $valor . "';";
			eval($asignacion);
		}
	}else{
		foreach($_GET as $nombre_campo => $valor){
			$asignacion = "\$" . $nombre_campo . "='" . $valor . "';";
			eval($asignacion);
		}
	}
	
	$obj_miconexion = fun_crear_objeto_conexion();
	$li_id_conex = fun_conexion($obj_miconexion);
	
	$obj_miconexion_1 = fun_crear_objeto_conexion();
	$li_id_conex_1 = fun_conexion($obj_miconexion);
	
	$co_usuario  =  $_SESSION["li_cod_usuario"];
	$arr_cliente =  Combo_Cliente();
	$arr_rubro   =  Combo_Rubro();
	$x_fecha_actual = date('d/m/Y h:i');
	

/*-------------------------------------------------------------------------------------------
	RUTINAS: MOSTRAR DATOS
-------------------------------------------------------------------------------------------*/
	$ls_sql ="SELECT pk_factura, fk_responsable, UPPER(s01_persona.tx_nombre), to_char(fe_fecha_factura,'DD/MM/YYYY') ,  
				tx_nota, tx_concepto,  nu_total, nu_subtotal, f_calcular_abono($x_movimiento),
				(nu_total - f_calcular_abono($x_movimiento)) as Debe
				FROM t20_factura
				INNER JOIN s01_persona ON s01_persona.co_persona = t20_factura.fk_cliente
				WHERE pk_factura = $x_movimiento";
	
	$ls_resultado =  $obj_miconexion->fun_consult($ls_sql);
	if($ls_resultado != 0){
		$row = pg_fetch_row($ls_resultado,0);
		$id_factura      = $row[0];
		$co_usuario	    = $row[1];
		$o_cliente  	= $row[2];	
		$o_fecha        = $row[3];
		$o_nota      = $row[4];
		$x_observacion  = $row[5];
		$x_total        = $row[6];
		$x_subtotal     = $row[7];
		$x_abono    	= $row[8];
		$x_debe    	= $row[9];
		
		// Extrae el detalle de la factura
		$ls_sql ="SELECT t02_proyecto.tx_nombre, nb_articulo, t01_detalle.nu_cant_item, t01_detalle.nu_cantidad, t01_detalle.nu_precio, 
						t01_detalle.nu_cantidad * t01_detalle.nu_precio as total, pk_detalle 
						FROM t01_detalle 
						INNER JOIN t02_proyecto ON t02_proyecto.pk_proyecto = t01_detalle.fk_rubro 
						INNER JOIN t13_articulo ON t13_articulo.pk_articulo =t01_detalle.fk_articulo
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
	
/*-------------------------------------------------------------------------------------------
	RUTINAS: Muestra la lista de ABONOS realizados
-------------------------------------------------------------------------------------------*/
  	$i=0;
	$ls_sql = "SELECT to_char(fe_fecha,'DD/MM/YYYY'), UPPER(s01_persona.tx_nombre||' '||s01_persona.tx_apellido), tx_observacion, nu_monto, pk_abono
					FROM t04_abono
					INNER JOIN s01_persona ON s01_persona.co_persona = t04_abono.fk_indicador
					WHERE fk_factura= $x_movimiento";
					
	//echo $ls_sql;	
	$ls_resultado_1 =  $obj_miconexion_1->fun_consult($ls_sql);			
	if($ls_resultado_1 != 0){
		$tarea = "M";
	}else{
		fun_error(1,$li_id_conex_1,$ls_sql,$_SERVER[PHP_SELF]);
	}

?>
<div class="main-content">
	<div class="main-content-inner">
		<div class="page-content">
			
			<div class="page-header">
				<h1>Venta </h1>
			</div><!-- /.page-header -->
					
			<div class="row">
				<div class="col-sm-12 ">
					<div class="widget-box transparent">
						<div class="widget-body">
							<div class="widget-main ">
							
						<div class="col-sm-6">
							<div class="row">
								<div class="col-xs-11 label label-lg label-info arrowed-in arrowed-right">
									<b>Factura</b>
								</div>
							</div>

							<div>
								<ul class="list-unstyled spaced">
									<li>
										<i class="ace-icon fa fa-caret-right blue"></i>
										<b class="blue"><?php echo $o_cliente; ?></b>
									</li>
									
									<li>
										<i class="ace-icon fa fa-caret-right blue"></i>
										Factura:
										<b class="black"><?php echo $o_nota; ?></b>
									</li>
									
									<li>
										<i class="ace-icon fa fa-caret-right blue"></i>
										Fecha:
										<b class="black"><?php echo $o_fecha; ?></b>
									</li>
									
									<li>
										<i class="ace-icon fa fa-caret-right blue"></i>
										Abono:
										<b class="black"><?php echo number_format($x_abono,2,",","."); ?></b>
									</li>
									
									<li>
										<i class="ace-icon fa fa-caret-right blue"></i>
										Debe:
										<b class="black"><?php echo number_format($x_debe,2,",","."); ?></b>
									</li>
								</ul>
							</div>
						</div><!-- /.col -->
			 
						<div class="space-6"></div>

					<div class="row">
						<div class="col-xs-12">
							<form class="form-horizontal" name="formulario">
								<table class="table table-striped table-bordered">
									<thead>
										<tr class="info">
											<th class="">Rubro</th>
											<th class="">Concepto</th>
											<th class="hidden-480">Item</th>
											<th class="hidden-480">Cantidad</th>											
											<th class="hidden-480">Precio</th>
											<th class="">Total</th>
										</tr>
									</thead>
									<tbody>	
										<?php
											$li_numcampo = $obj_miconexion->fun_numcampos()-7; // Columnas que se muestran en la Tabla
											$li_indicecampo = $obj_miconexion->fun_numcampos()-1; // Referencia al indice de la columna clave
											fun_dibujar_tabla($obj_miconexion,$li_numcampo,$li_indicecampo, 'VER_FACTURA', 0); // Dibuja la Tabla de Datos
											$obj_miconexion->fun_closepg($li_id_conex,$ls_resultado);										
										?>
									</tbody>
								</table>
								<input type="hidden" name="x_movimiento" value="<?php echo $x_movimiento;?>">
								<input type="hidden" name="x_pagar" 	value="<?php echo $x_pagar;?>">
								<input type="hidden" name="tarea" 		value="<?php echo $tarea;?>">
								<input type="hidden" name="x_vendedor"  value="<?php echo $x_vendedor;?>">
								<input type="hidden" name="x_cliente"   value="<?php echo $x_cliente;?>">   
								<input type="hidden" name="x_fecha" value="<?php echo $x_fecha;?>">
								<input type="hidden" name="input_filtro" 	value="<?php echo $input_filtro;?>">	
								<input type="hidden" name="filtro" 		 value="<?php echo $filtro;?>">		
							</form>							
						</div>
					</div> <!-- /.row tabla dealle -->				
					
					<div class="row">
						<div class="col-sm-5 pull-right">
							<h4 class="pull-right">
								Total :
								<span class="blue"><?php echo number_format($x_total,2,",","."); ?></span>
							</h4>
						</div>
					</div>
						
					<div class="row">
						<div class="col-xs-12">
							<table class="table table-striped table-bordered">
								<thead>
								<tr class="info" >
									<th>Fecha</th>									
									<th>Responsable</th>
									<th>Referencia</th>
									<th>Abono</th>
								</tr>
							</thead>
							<tbody>	
								<?php   
										$li_numcampo = $obj_miconexion_1->fun_numcampos()-1; // Columnas que se muestran en la Tabla
										$li_indicecampo = $obj_miconexion_1->fun_numcampos()-1; // Referencia al indice de la columna clave
										fun_dibujar_tabla($obj_miconexion_1,$li_numcampo,$li_indicecampo, 'NO_LISTAR_ABONO',0); // Dibuja la Tabla de Datos
										$obj_miconexion->fun_closepg($li_id_conex_1,$ls_resultado_1);
								?>
							</tbody>
							</table>
						</div>
					</div> <!-- /.row tabla abonos -->	

					<div class="row">
						<div class="col-sm-5 pull-right">
							<h4 class="pull-right">
								 Abono:
								<span class="blue"><?php echo number_format($x_abono,2,",","."); ?></span>
							</h4>
						</div>
					</div>	
					<div class="hr hr8 hr-double hr-dotted"></div>
					<div class="row">
						<div class="col-sm-5 pull-right">
							<h4 class="pull-right">
								 Debe:
								<span class="red"><?php echo number_format($x_debe,2,",","."); ?></span>
							</h4>
						</div>
					</div>	
								
					<div class="row">
						<div class="col-xs-12 ">
							<div class="form-group center">												
								<button class="btn btn-sm  btn-danger" onclick="Atras()">
									<i class="ace-icon fa fa-reply align-top bigger-125 "></i>
									Regresar
								</button>																							
							</div>
						</div>
					</div>
					
					</div>
					</div>
					</div>
				</div> 
			</div> 
		</div> <!-- ROW CONTENT END -->
	</div> <!-- /.page-content -->

	<script src="../../assets/js/jquery.2.1.1.min.js"></script>
	<script src="../../assets/js/bootstrap.min.js"></script>
	<script src="../../assets/js/jquery-ui.custom.min.js"></script>
	<script src="../../assets/js/ace-elements.min.js"></script>
	<script src="../../assets/js/ace.min.js"></script>

	<!-- inline scripts related to this page -->
	<script type="text/javascript">
		 
		 $(document).ready(function() {
			window.parent.ScrollToTop(); // invoca la funcion ScrollToTop que se encuentra en interace.php para posicionar el scroll vertical
		
		} );
	
	</script>
		
	<script type="text/javascript"> 

		function Atras(parametros){
			document.formulario.tarea.value = "X";
			document.formulario.action = "adm_venta_view.php";
			document.formulario.method = "POST";
			document.formulario.submit();
		}	
	
	</script>
</body>
</html>