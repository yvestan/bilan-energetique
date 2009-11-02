<?php 

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Page de génération des images d'un bilan énergétique
 *
 * PHP version 5
 *
 * LICENSE: Ce programme est un logiciel libre distribue sous licence GNU/GPL
 *
 * @author     Yves Tannier <yvesSANSPAM@gmail.com>
 * @copyright  2009 Yves Tannier
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    0.1.0
 * @link       http://www.grafactory.net
 */

// classe bilan
require 'bilan.class.php'; 

// paramètres
$bilan_energetique_params = array(
    'path_src' => '/path/to/src/',
    'path_img' => '/path/to/img/',
    'path_font' => '/path/to/src/DejaVuSans.ttf',
    'cmd_convert' => '/usr/bin/convert',
    'cmd_composite' => '/usr/bin/composite',
    'cmd_mogrify' => '/usr/bin/mogrify',
);

// instanciation
$bilan_energetique = new Bilan_energetique($bilan_energetique_params);

// image = identifiant de session
session_start();
$img_name = session_id(); 

$fields = array(
    'conso_energie' => array(
        'label' => 'Consommation conventionnelle',
        'control' => array('required','numeric'),
    ),
    'emission_ges' => array(
        'label' => 'Estimation des émissions',
        'control' => array('required','numeric'),
    ),
);

// récupérer les valeurs des champs et les tester
if (!empty($_POST['submit'])) {
    foreach($fields as $k=>$v) {
        if(!empty($v['control'])) {
            foreach($v['control'] as $control) {
                // le champ est requis
                if($control=='required') {
                    if(empty($_POST[$k])) {
                        $error[$k] = '<i>'.$v['label'].'</i> : vous devez préciser ce champ.';
                    }
                }
                // le champ doit-être numérique
                if($control=='numeric') {
                    if(!is_numeric($_POST[$k])) {
                        $error[$k] = '<i>'.$v['label'].'</i> : cette valeur doit-être un nombre';
                    }
                }
            }
        }
    }
}

$style_more = array('style.css');
$code_source = true;
$title_more = 'Génération en ligne de graphique de bilans énergétiques';
include '../header.inc.php';
?>
    <div id="bilan">

        <h1 class="titre">Générer vos graphiques de bilan énergétique</h1>

        <?php if(empty($error) && !empty($_POST['submit'])) { ?>

        <h2>Consommations annuelles par énergie</h2>
        <p>Obtenues par la méthode 3CL, version 15C, estimé à l'immeuble ou au logement, prix moyen des énergies indexés au 15 août 2007. </p>
        
        <div class="colonne_deux">
          <h2>Consommations énergétiques <span class="normal">(en énergie primaire)</span> 
            <br /><span class="sstitre">pour le chauffage, la production d'eau chaude sanitaire et le refroidissement</span></h2>
          <h3>Consommation conventionnelle</h3>
          <div class="graphique">
            <img src="./img/<?php echo $bilan_energetique->generateImage('conso_energie', 'conso_energie-'.$img_name.'.jpg', (int)$_POST['conso_energie']); ?>" alt="Graphique de la consommation conventionnelle" />
          </div>
        </div>

        <div class="colonne_deux" style="margin-left:25px;">
          <h2>Emission de gaz à effet de serre (GES)
             <br /><span class="sstitre">pour le chauffage, la production d'eau chaude sanitaire et le refroidissement</span></h2>
          <h3>Estimation des émissions</h3>
          <div class="graphique">
            <img src="./img/<?php echo $bilan_energetique->generateImage('emission_ges', 'emission_ges-'.$img_name.'.jpg', (int)$_POST['conso_energie']); ?>" alt="Graphique de l'estimation des émissions" />
          </div>
        </div>

        <div class="spacer">&nbsp;</div>
        <?php } ?>


        <h2>Testez ! Générez vos images de bilan !</h2>

        <p class="intro">Cette application et la classe PHP fournie permettent simplement de générer les graphiques d'un bilan énergétique à partir des valeurs fournies.</p>

        <?php if(!empty($error)) { ?>
        <div class="error">
            <ul>
            <?php foreach($error as $e) { ?>
                <li><?php echo $e; ?></li>
            <?php } ?>
            </ul>
        </div>
        <?php } ?>

        <form method="post" id="bilan_form" action="./">

            <div><label for="conso_energie">Consommation conventionnelle</label></div>
            <p><input size="10" name="conso_energie" type="text" /></p>

            <div><label for="emission_ges">Estimation des émissions</label></div>
            <p><input size="10" name="emission_ges" type="text" /></p>

            <p class="bouton"><input name="submit" value="Générer les graphiques" type="submit" /></p>
           
        </form>

        <div id="footer_bilan">
            Cette page est placée sous Licence <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/2.0/fr/">
                <img alt="Creative Commons License" style="border-width: 0pt; vertical-align: middle;" src="http://i.creativecommons.org/l/by-nc-sa/2.0/fr/80x15.png" /></a> 
        </div>

    </div>

<?php include '../footer.inc.php'; ?>
