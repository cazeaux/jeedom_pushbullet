<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}


/************* CONSTRUCTION SELECTEURS PAYS et STATIONS ****************/
$path_to_icao_db=substr(dirname(__FILE__),0,strpos (dirname(__FILE__),'/plugins/pushbullet')).'/plugins/pushbullet/core/ressources/';

$plugin = plugin::byId('pushbullet');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="icon loisir-two28"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add" >
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
		</div>
		<legend><i class="fas fa-phone"></i> {{Mes Pushbullet}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '" >';
				echo '<img src="' . $plugin->getPathImgIcon() . '" />';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>
    <div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>   
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#eqparatab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-wrench"></i> {{Paramètres}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Nom de l'équipement virtuel}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement virtuel}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" >{{Objet parent}}</label>
							<div class="col-sm-3">
								<select class="form-control eqLogicAttr" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
									foreach (jeeObject::all() as $object) {
										echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Catégorie}}</label>
							<div class="col-sm-8">
								<?php
								foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
									echo '<label class="checkbox-inline">';
									echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
									echo '</label>';
								}
								?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label"></label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
							</div>
                        </div>
					</fieldset>
				</form>
			</div>
            <div role="tabpanel" class="tab-pane" id="eqparatab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <legend><i class="fas fa-wrench"></i>  {{Paramètres}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Token Pushbullet}}</label>
                            <div class="col-md-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="token" placeholder="token"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom du device Jeedom dans Pushbullet}}</label>
                            <div class="col-md-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="jeedomDeviceName" placeholder="jeedom device name"/>
                            </div>
                        </div>
                        <div class="form-group">
							<label class="col-sm-3 control-label">{{Activer l'envoi de commandes vers jeedom via cet équipement}}</label>
							<div class="col-sm-8">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="isPushEnabled" checked/>{{Activer}}
							</div>
						</div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Envoyer la réponses des interactions au device qui a émis la commande (si Activer, ce paramètre se substitue au choix fait au niveau de chaque device)}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="sendBackReponseToSource" checked/>{{Activer}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer les interactions sur cet équipement}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="isInteractionEnabled" checked/>{{Activer}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Inclure la commande reçue dans les réponses envoyées après exécution d'une interaction}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="dismissInitialCommandeInReply"/>{{Activer}}
                            </div>
                        </div>                        
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Ecouter les push entrant sur jeedom "envoyés à tous" (en plus de ceux explicitement envoyés à Jeedom)}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="listenAllPushes" />{{Activer}}
                            </div>
                        </div>                        
                        <br />
                        <br />
                        <legend><i class="fas fa-terminal"></i>  {{Dernière commande reçue}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Timestamp}}</label>
                            <div class="col-md-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="timestamp" readonly="true" placeholder="timestamp du dernier push reçu"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Dernière valeur push}}</label>
                            <div class="col-md-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="lastvalue" readonly="true" placeholder="valeur du dernier push reçu"/>
                            </div>
                        </div> 
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br/>
                <div class="alert alert-info">
                    {{Pour un parfaite intégration de PushBullet et Jeedom, dans votre scenario PushBullet il faut mettre dans titre "$title$" et dans message "$message$".}}
                </div>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
                        <tr>
                            <th style="width: 20px;">{{ID}}</th>
                            <th style="width: 50px;">{{Type}}</th>
                            <th style="width: 350px;">{{Nom du service}}</th>
                            <th style="width: 150px;">{{Options}}</th>
                            <th style="width: 20px;">{{Actions}}</th>
                        </tr>
					</thead>
					<tbody>
						
					</tbody>
				</table>
				
			</div>
		</div>
    </div>
</div>

<?php include_file('desktop', 'pushbullet', 'js', 'pushbullet'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>