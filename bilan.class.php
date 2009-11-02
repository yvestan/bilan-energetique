<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Génération des images d'un bilan énergétique
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

class Bilan_energetique
{

    // path de stockage des images d'origine
    protected $path_img;

    // path de stockage des images pour la composition
    protected $path_src;

    // chemin de la font
    protected $path_font;

    // chemin vers les executables imagemagick
    protected $cmd_convert;
    protected $cmd_composite;
    protected $cmd_mogrify;

    // variable d'erreur
    protected $error = array();

    // debug mode 
    protected $debug_mode = false;
    protected $debug;

    // {{{ __construct()

    /** Constructeur
     *
     * @param   array   $params Paramètres
     */
    public function __construct($params=array())
    {

        // paramètres obligatoires
        $need_params = array('path_src','path_font','cmd_convert','cmd_composite','cmd_mogrify');

        foreach($need_params as $v) {
            if(empty($params[$v])) {
                throw new Exception('[params_'.$v.'] Vous devez préciser le paramètre obligatoire '.$v);
            } else {
                $this->$v = $params[$v];
            }
        }

        // le répertoire des images existe, peut-aussi être précisé pour chaque image
        if(!empty($params['path_img'])) {
            $this->setPathImg($params['path_img']);
        }

    }

    // }}}

    // {{{ generateImage()

    /** Générer l'image
     *
     * @return  string|bool Retourne l'url de l'image ou une erreur
     * @param   string  $type_image Image à générer
     * @param   string  $img_out    Chemin et nom complet de l'image en sortie
     * @param   int     $value      Valeur
     */
    public function generateImage($type_image,$img_out,$value)
    {

        // propriété de l'image à générer
        $image_type = $this->getTypesImages($type_image);

        if(empty($image_type)) {
            throw new Exception('[image_type] Ce type d\'image n\'existe pas dans la classe '.$image_type);
        }


        // valeur en plus en pixel depuis le haut de l'image
        $hauteur_plus = 22;

        // hauteur d'une section en pixel
        $hauteur_section = 28;
        
        // on stoppe si une image existe déjà
        if(file_exists($img_out)) {
            @unlink($img_out);
        }

        // calcul position
        $h = 0;

        // si au dela des maximum on riche
        if($value>$image_type['max']) {
            $max_value = $value;
            $value = $image_type['max'];
        }

        // si en dessous de 0 on triche (?!)
        if($value<=0) {
            $max_value = $value;
            $value = 1;
        }

        foreach($image_type['tranche'] as $lettre=>$v) {

            if($value>=$v['min'] && $value<=$v['max']) {

                // hauteur depuis le haut
                $hauteur_haut = $h;

                // ecart variable
                $ecart = $v['max']-$v['min'];

                // ecart entre le début de la zone et la valeur
                $ecart_zone = $value-$v['min'];

                // x en pixel depuis le haut de la zone
                $x_haut_zone = ($hauteur_section/$ecart)*$ecart_zone;

                // x total
                $x = $hauteur_haut+$hauteur_plus+$x_haut_zone;

                // debugage
                if($this->isDebug()) {
                    $this->debug += array(
                        'Zone '.$lettre.'  pour la valeur recherchée '.$value.' kw',
                        'hauteur depuis le haut du graphique h = '.$hauteur_haut.' px',
                        'Ecart de la zone ecart entre '.$v['a'].' et '.$v['b'].' = '.$ecart.' kw',
                        'Ecart début de la zone/valeur '.$ecart_zone,
                        'Résultat de '.$hauteur_section.'/'.$ecart.'*'.$ecart_zone.'='.$x_haut_zone,
                    );
                }

                $ecart_trouve = true;

                break;

            } else {
                $h = $h+$hauteur_section;
            }
        }

        // on retire la moitié de la taille de l'image du curseur
        $x = $x-18;

        // debugage
        if($this->isDebug()) {
            $this->debug[] = 'Valeur de x '.$x;
        }
        
        $y = 31;
        $xt = $x+22;

        // reprend la "vraie" valeur pour l'inscrire sur la flèche
        if(!empty($max_value)) {
            $value = $max_value;
        }

        $this->execImage($type_image,$img_out,$x,$y,$xt,$value);

        return $img_out;

    }

    // }}}

    // {{{ execImage()

    /** Les deux types de graphiques
     *
     * @return  array
     * @param   string  $type_image     Le type demandé
     */
    protected function execImage($type_image,$img_out,$x,$y,$xt,$value)
    {

        if(!is_dir($this->path_img)) {
            throw new Exception('[path_img_exist] Le répertoire de destination '.$this->path_img.' est introuvable');
        }

        if(!is_dir($this->path_src)) {
            throw new Exception('[path_src_exist] Le répertoire des images de composition '.$this->path_src.' est introuvable');
        }

        if(!file_exists($this->path_font)) {
            throw new Exception('[path_font_exist] La police de caractère est introuvable '.$this->path_font);
        }

        // faire le montage
        $exec1 = $this->cmd_composite.' -geometry +'.$y.'+'.$x.' '.$this->path_src.'/'.$type_image.'_c.png -compose dst_over '.$this->path_src.'/'.$type_image.'.png '.$this->path_img.'/'.$img_out.'.png';
        $exec2 = $this->cmd_convert.' '.$this->path_img.'/'.$img_out.'.png -background white -flatten '.$this->path_img.'/'.$img_out;
        $exec3 = $this->cmd_mogrify.' -font '.$this->path_font.' -pointsize 13 -fill \'#ffffff\' -annotate +255+'.$xt.' \''.$value.'\' '.$this->path_img.'/'.$img_out;

        // debugage
        if($this->isDebug()) {
            $this->debug += array(
                $exec1,
                $exec2,
                $exec3,
            );
        }

        // execution
        exec($exec1);
        exec($exec2);
        exec($exec3);

        // supprime le png temporaire
        if(file_exists($img_out.'.png')) {
            @unlink($this->path_img.'/'.$img_out.'.png');
        }

    }

    // }}}

    // {{{ geTypesImages

    /** Les deux types de graphiques
     *
     * @return  array
     * @param   string  $type_image     Le type demandé
     */
    protected function getTypesImages($type_image=null)
    {

        $images_type = array(
            'conso_energie' => array(
                'img' => 'conso-energie-prod', 
                'echelle' => 450,
                'max' => 550,
                'tranche' => array(
                    'A' => array('min' => 0, 'max' => 50),
                    'B' => array('min' => 51, 'max' => 90),
                    'C' => array('min' => 91, 'max' => 150),
                    'D' => array('min' => 151, 'max' => 230),
                    'E' => array('min' => 231, 'max' => 330),
                    'F' => array('min' => 331, 'max' => 450),
                    'G' => array('min' => 450, 'max' => 550),
                ),
            ),
            'emission_ges' => array(
                'img' => 'emission-ges-prod', 
                'echelle' => 80,
                'max' => 100,
                'tranche' => array(
                    'A' => array('min' => 0, 'max' => 5),
                    'B' => array('min' => 6, 'max' => 10),
                    'C' => array('min' => 11, 'max' => 20),
                    'D' => array('min' => 21, 'max' => 35),
                    'E' => array('min' => 36, 'max' => 55),
                    'F' => array('min' => 56, 'max' => 80),
                    'G' => array('min' => 80, 'max' => 100),
                ),
            ),
        );

        if(!empty($images_type[$type_image])) {
            return $images_type[$type_image];
        }

        return $images_type;

    }

    // }}}

    // {{{ setPathImg()

    /** Chemin absolu vers l'image finale
     *
     * Peux-être précisé dans le constructeur ou pour chaque image
     *
     * @param   string  $path_img     Chemin
     */
    public function setPathImg($path_img)
    {

        // tester l'existence
        if(!is_dir($path_img)) {
            throw new Exception('[path_img_setPathImg] Le chemin '.$path_img.' n\'est pas correct');
        } else {
            $this->path_img = $path_img;
        }

    }   

    // }}}

    // {{{ isDebug()

    /** Mode débugage ?
     *
     * @return bool
     */
    protected function isDebug() { return $this->debug_mode; }   

    // }}}

    // {{{ getDebug()

    /** Récupérer les infos de débug
     *
     * @return array
     */
    public function getDebug() { return $this->debug; }   

    // }}}


}
?>
