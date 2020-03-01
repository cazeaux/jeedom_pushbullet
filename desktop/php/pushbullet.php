<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('pushbullet');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add" >
                <i class="fas fa-plus-circle"></i>
                <br/>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
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
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>
        
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>
        
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal col-sm-10">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l\'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
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
                            <label class="col-sm-3 control-label">{{Catégorie}}</label>
                            <div class="col-sm-9">
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
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>
                    </fieldset>
                </form>
                
                <form class="form-horizontal col-sm-2">
                    <fieldset>
                        <div class="form-group">
                            <img src="<?php echo $plugin->getPathImgIcon(); ?>" style="width:120px;" />
                        </div>
                    </fieldset>
                </form>
                <br/>
                
                <hr>
                
                <legend><i class="fas fa-cog"></i> {{Paramètres}}</legend>
                <form class="form-horizontal col-sm-10">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Token Pushbullet}}</label>
                            <div class="col-md-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="token" placeholder="token"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom du device Pushbullet}}
                                <sup><i class="fas fa-question-circle" title="{{Nom du device Jeedom dans Pushbullet}}"></i></sup>
                            </label>
                            <div class="col-md-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="jeedomDeviceName" placeholder="jeedom device name"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer l'envoi de commandes}}
                                <sup><i class="fas fa-question-circle" title="{{Activer l'envoi de commandes vers jeedom via cet équipement}}"></i></sup>
                            </label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="isPushEnabled" checked/>{{Activer}}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Envoyer la réponse des interactions au device}}
                                <sup><i class="fas fa-question-circle" title="{{Envoyer la réponse des interactions au device qui a émis la commande (si Activer, ce paramètre se substitue au choix fait au niveau de chaque device)}}"></i></sup>
                            </label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="sendBackReponseToSource" checked/>{{Activer}}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer les interactions sur cet équipement}}</label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="isInteractionEnabled" checked/>{{Activer}}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Inclure la commande reçue dans les réponses}}
                                <sup><i class="fas fa-question-circle" title="{{Inclure la commande reçue dans les réponses envoyées après exécution d'une interaction}}"></i></sup>
                            </label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="dismissInitialCommandeInReply"/>{{Activer}}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Ecouter les push entrant}}
                                <sup><i class="fas fa-question-circle" title="{{Ecouter les push entrant sur jeedom envoyés à tous (en plus de ceux explicitement envoyés à Jeedom)}}"></i></sup>
                            </label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="listenAllPushes" />{{Activer}}
                                </label>
                            </div>
                        </div>
                        <br/>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Timestamp}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="timestamp" readonly="true" placeholder="timestamp du dernier push reçu"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Dernière valeur push}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="lastvalue" readonly="true" placeholder="valeur du dernier push reçu"/>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th width="50px"> ID</th>
                            <th width="80px">{{Type}}</th>
                            <th>{{Nom du device}}</th>
                            <th>{{Device recevant la réponse}}</th>
                            <th>{{Paramètres}}</th>
                            <th width="120px;">{{Options}}</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
    include_file('desktop', 'pushbullet', 'js', 'pushbullet');
    include_file('core', 'plugin.template', 'js');
?>