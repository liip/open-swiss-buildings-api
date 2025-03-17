<?php

declare(strict_types=1);

namespace App\Domain\Registry\DataCH\Entity;

use App\Domain\Registry\DataCH\Model\SwissBuildingStatusEnum;
use App\Domain\Registry\DataCH\Repository\BuildingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'building')]
#[ORM\Entity(repositoryClass: BuildingRepository::class, readOnly: true)]
class Building
{
    /**
     * Eidgenössischer Gebäudeidentifikator
     * Identificateur fédéral de bâtiment
     * Identificatore federale dell’edificio.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'EGID', length: 9)]
    public string $EGID;

    /**
     * Kantonskürzel
     * Canton
     * Abbreviazione del Cantone.
     */
    #[ORM\Column(name: 'GDEKT', length: 2)]
    public string $GDEKT;

    /**
     * BFS-Gemeindenummer
     * Numéro OFS de la commune
     * Numero UST del Comune.
     */
    #[ORM\Column(name: 'GGDENR', length: 4)]
    public string $GGDENR;

    /**
     * Gemeindename
     * Nom de la commune
     * Nome del Comune.
     */
    #[ORM\Column(name: 'GGDENAME', length: 40)]
    public string $GGDENAME;

    /**
     * Eidgenössischer Grundstücksidentifikator
     * Identificateur fédéral d'immeuble (bien-fonds)
     * Identificatore federale del fondo (immobile).
     */
    #[ORM\Column(name: 'EGRID', length: 14)]
    public string $EGRID;

    /**
     * Grundbuchkreisnummer
     * N° de secteur du registre foncier
     * Numero di sezione del registro fondiario.
     */
    #[ORM\Column(name: 'LGBKR', length: 4)]
    public string $LGBKR;

    /**
     * Grundstücksnummer
     * Numéro d’immeuble
     * Numero della particella.
     */
    #[ORM\Column(name: 'LPARZ', length: 12)]
    public string $LPARZ;

    /**
     * Suffix der Grundstücksnummer
     * Suffixe du numéro d’immeuble
     * Suffisso del numero del fondo.
     */
    #[ORM\Column(name: 'LPARZSX', length: 12)]
    public string $LPARZSX;

    /**
     * Typ des Grundstücks
     * Type d'immeuble
     * Tipo di fondo.
     */
    #[ORM\Column(name: 'LTYP', length: 4)]
    public string $LTYP;

    /**
     * Amtliche Gebäudenummer
     * Numéro officiel du bâtiment
     * Numero ufficiale dell’edificio.
     */
    #[ORM\Column(name: 'GEBNR', length: 12)]
    public string $GEBNR;
    /**
     * Name des Gebäudes
     * Designation du bâtiment
     * Nome dell’edificio.
     */
    #[ORM\Column(name: 'GBEZ', length: 40)]
    public string $GBEZ;

    /**
     * E-Gebäudekoordinate
     * Coordonnée E
     * Coordinata E dell’edificio.
     */
    #[ORM\Column(name: 'GKODE', length: 11)]
    public string $GKODE;

    /**
     * N-Gebäudekoordinate
     * Coordonnée N
     * Coordinata N dell’edificio.
     */
    #[ORM\Column(name: 'GKODN', length: 11)]
    public string $GKODN;

    /**
     * Koordinatenherkunft
     * Provenance des coordonnées
     * Provenienza delle coordinate.
     */
    #[ORM\Column(name: 'GKSCE', length: 3)]
    public string $GKSCE;

    /**
     * Gebäudestatus
     * Statut du bâtiment
     * Stato dell’edificio.
     */
    #[ORM\Column(name: 'GSTAT', length: 4, enumType: SwissBuildingStatusEnum::class)]
    public SwissBuildingStatusEnum $GSTAT;

    /**
     * Gebäudekategorie
     * Catégorie de bâtiment
     * Categoria di edificio.
     */
    #[ORM\Column(name: 'GKAT', length: 4)]
    public string $GKAT;

    /**
     * Gebäudeklasse
     * Classe de bâtiment
     * Classe di edificio.
     */
    #[ORM\Column(name: 'GKLAS', length: 4)]
    public string $GKLAS;

    /**
     * Baujahr des Gebäudes YYYY
     * Année de construction du bâtiment
     * Anno di costruzione dell’edificio.
     */
    #[ORM\Column(name: 'GBAUJ', length: 4)]
    public string $GBAUJ;

    /**
     * Baumonat des Gebäudes MM
     * Mois de construction du bâtiment
     * Mese di costruzione dell’edificio.
     */
    #[ORM\Column(name: 'GBAUM', length: 2)]
    public string $GBAUM;

    /**
     * Bauperiode
     * Période de construction
     * Epoca di costruzione.
     */
    #[ORM\Column(name: 'GBAUP', length: 4)]
    public string $GBAUP;

    /**
     * Abbruchjahr des Gebäudes
     * Année de démolition du bâtiment
     * Anno di demolizione dell’edificio.
     */
    #[ORM\Column(name: 'GABBJ', length: 4)]
    public string $GABBJ;

    /**
     * Gebäudefläche
     * Surface du bâtiment
     * Superficie dell’edificio.
     */
    #[ORM\Column(name: 'GAREA', length: 5)]
    public string $GAREA;

    /**
     * Gebäudevolumen
     * Volume du bâtiment
     * Volume dell’edificio.
     */
    #[ORM\Column(name: 'GVOL', length: 7)]
    public string $GVOL;

    /**
     * Gebäudevolumen: Norm
     * Volume du bâtiment : norme
     * Volume dell’edificio: norma.
     */
    #[ORM\Column(name: 'GVOLNORM', length: 3)]
    public string $GVOLNORM;

    /**
     * Informationsquelle zum Gebäudevolumen
     * Volume du bâtiment : indication sur la donnée
     * Volume dell’edificio: indicazione sul dato.
     */
    #[ORM\Column(name: 'GVOLSCE', length: 3)]
    public string $GVOLSCE;

    /**
     * Anzahl Geschosse
     * Nombre de niveaux
     * Numero di piani.
     */
    #[ORM\Column(name: 'GASTW', length: 2)]
    public string $GASTW;

    /**
     * Anzahl Wohnungen
     * Nombre de logements
     * Numero di abitazioni.
     */
    #[ORM\Column(name: 'GANZWHG', length: 3)]
    public string $GANZWHG;

    /**
     * Anzahl separate Wohnräume
     * Nombre de pièces d’hab. indép.
     * Numero di locali abitabili indipendenti.
     */
    #[ORM\Column(name: 'GAZZI', length: 3)]
    public string $GAZZI;

    /**
     * Zivilschutzraum
     * Abri de protection civile
     * Rifugio di protezione civile.
     */
    #[ORM\Column(name: 'GSCHUTZR', length: 1)]
    public string $GSCHUTZR;

    /**
     * Energiebezugsfläche
     * Surface de référence énergétique
     * Superficie di riferimento energetico.
     */
    #[ORM\Column(name: 'GEBF', length: 6)]
    public string $GEBF;

    /**
     * Wärmeerzeuger Heizung 1
     * Générateur de chaleur pour le chauffage 1
     * Generatore di calore per il riscaldamento 1.
     */
    #[ORM\Column(name: 'GWAERZH1', length: 4)]
    public string $GWAERZH1;

    /**
     * Energie-/Wärmequelle Heizung 1
     * Source d’énergie / de chaleur pour le chauffage 1
     * Fonte di energia / di calore per il riscaldamento 1.
     */
    #[ORM\Column(name: 'GENH1', length: 4)]
    public string $GENH1;

    /**
     * Informationsquelle Heizung 1
     * Source d‘information pour le chauffage 1
     * Fonte d’informazione per il riscaldamento 1.
     */
    #[ORM\Column(name: 'GWAERSCEH1', length: 3)]
    public string $GWAERSCEH1;

    /**
     * Aktualisierungsdatum Heizung 1
     * Date de mise à jour pour le chauffage 1
     * Data dell’aggiornamento per il riscaldamento 1.
     */
    #[ORM\Column(name: 'GWAERDATH1', length: 10)]
    public \DateTimeImmutable $GWAERDATH1;

    /**
     * Wärmeerzeuger Heizung 2
     * Générateur de chaleur pour le chauffage 2
     * Generatore di calore per il riscaldamento 2.
     */
    #[ORM\Column(name: 'GWAERZH2', length: 4)]
    public string $GWAERZH2;

    /**
     * Energie-/Wärmequelle Heizung 2
     * Source d’énergie / de chaleur pour le chauffage 2
     * Fonte di energia / di calore per il riscaldamento 2.
     */
    #[ORM\Column(name: 'GENH2', length: 4)]
    public string $GENH2;

    /**
     * Informationsquelle Heizung 2
     * Source d‘information pour le chauffage 2
     * Fonte d’informazione per il riscaldamento 2.
     */
    #[ORM\Column(name: 'GWAERSCEH2', length: 3)]
    public string $GWAERSCEH2;

    /**
     * Aktualisierungsdatum Heizung 2
     * Date de mise à jour pour le chauffage 2
     * Data dell’aggiornamento per il riscaldamento 2.
     */
    #[ORM\Column(name: 'GWAERDATH2', length: 10)]
    public \DateTimeImmutable $GWAERDATH2;

    /**
     * Wärmeerzeuger Warmwasser 1
     * Générateur de chaleur pour l'eau chaude 1
     * Generatore di calore per l’acqua calda 1.
     */
    #[ORM\Column(name: 'GWAERZW1', length: 4)]
    public string $GWAERZW1;

    /**
     * Energie-/Wärmequelle Warmwasser 1
     * Source d’énergie / de chaleur pour l'eau chaude 1
     * Fonte d’energia / di calore per l’acqua calda 1.
     */
    #[ORM\Column(name: 'GENW1', length: 4)]
    public string $GENW1;

    /**
     * Informationsquelle Warmwasser 1
     * Source d‘information pour l'eau chaude 1
     * Fonte d’informazione per l’acqua calda 1.
     */
    #[ORM\Column(name: 'GWAERSCEW1', length: 3)]
    public string $GWAERSCEW1;

    /**
     * Aktualisierungsdatum Warmwasser 1
     * Date de mise à jour pour l'eau chaude 1
     * Data d’aggiornamento per l’acqua calda 1.
     */
    #[ORM\Column(name: 'GWAERDATW1', length: 10)]
    public \DateTimeImmutable $GWAERDATW1;

    /**
     * Wärmeerzeuger Warmwasser 2
     * Générateur de chaleur pour l'eau chaude 2
     * Generatore di calore per l’acqua calda 2.
     */
    #[ORM\Column(name: 'GWAERZW2', length: 4)]
    public string $GWAERZW2;

    /**
     * Energie-/Wärmequelle Warmwasser 2
     * Source d’énergie / de chaleur pour l'eau chaude 2
     * Fonte d’energia / di calore per l’acqua calda 2.
     */
    #[ORM\Column(name: 'GENW2', length: 4)]
    public string $GENW2;

    /**
     * Informationsquelle Warmwasser 2
     * Source d‘information pour l'eau chaude 2
     * Fonte d’informazione per l’acqua calda 2.
     */
    #[ORM\Column(name: 'GWAERSCEW2', length: 3)]
    public string $GWAERSCEW2;

    /**
     * Aktualisierungsdatum Warmwasser 2
     * Date de mise à jour pour l'eau chaude 2
     * Data d’aggiornamento per l’acqua calda 2.
     */
    #[ORM\Column(name: 'GWAERDATW2', length: 10)]
    public \DateTimeImmutable $GWAERDATW2;

    /**
     * Datum des Exports
     * Date de l'export
     * Data dell'esportazione.
     */
    #[ORM\Column(name: 'GEXPDAT', length: 10)]
    public \DateTimeImmutable $GEXPDAT;
}
