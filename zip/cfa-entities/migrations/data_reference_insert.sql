-- =============================================================================
-- Script SQL d'insertion des données de référence pour le CFA
-- Tables : ref_niveau_qualification, ref_type_certification, ref_code_nsf, ref_code_rome
-- =============================================================================

-- Désactiver les vérifications de clés étrangères pendant l'insertion
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- TABLE : ref_niveau_qualification
-- Cadre national des certifications professionnelles (8 niveaux)
-- =============================================================================
TRUNCATE TABLE ref_niveau_qualification;

INSERT INTO ref_niveau_qualification (code, libelle, equivalent_diplome, description, ancien_niveau, niveau_cec, actif) VALUES
(1, 'Niveau 1 - Savoirs de base', 'Savoirs de base', 'Maîtrise des savoirs de base. Ce niveau correspond à la maîtrise de savoirs généraux de base pouvant contribuer à l''exercice d''une activité professionnelle.', NULL, 1, 1),
(2, 'Niveau 2 - Infra CAP', 'Infra CAP (aucune certification enregistrée à ce jour)', 'Maîtrise des savoirs de base et capacité à effectuer des activités simples et résoudre des problèmes courants à l''aide de règles et d''outils simples en mobilisant des savoir-faire professionnels dans un contexte structuré. Autonomie dans la réalisation de l''activité.', NULL, 2, 1),
(3, 'Niveau 3 - CAP/BEP', 'CAP, BEP, Mention complémentaire niveau 3, Titre professionnel niveau 3', 'Maîtrise des savoirs dans un champ d''activité. Capacité à effectuer des activités combinant des tâches simples et à résoudre des problèmes courants dans un contexte connu. Responsabilité d''un travail et/ou participation aux décisions dans un groupe restreint.', 'V', 3, 1),
(4, 'Niveau 4 - Baccalauréat', 'Baccalauréat (général, technologique, professionnel), BP, BT, Titre professionnel niveau 4', 'Maîtrise de savoirs dans un domaine d''activité élargi. Capacité à effectuer des activités nécessitant de mobiliser un large éventail de savoirs et savoir-faire dans un contexte changeant. Responsabilité pour la réalisation des activités et participation à l''évaluation des activités.', 'IV', 4, 1),
(5, 'Niveau 5 - Bac+2 (BTS/DUT)', 'BTS, BTSA, DUT, DEUST, Titre professionnel niveau 5', 'Maîtrise des savoir-faire dans un champ d''activité. Capacité à élaborer des solutions à des problèmes nouveaux, à analyser et interpréter des informations en mobilisant des concepts. Transmission du savoir-faire et des méthodes.', 'III', 5, 1),
(6, 'Niveau 6 - Licence/BUT', 'Licence, Licence professionnelle, BUT, Titre professionnel niveau 6', 'Maîtrise approfondie de savoirs hautement spécialisés. Capacité à analyser et résoudre des problèmes complexes imprévus dans un domaine spécifique, à formaliser des savoir-faire et des méthodes et à les capitaliser. Les diplômes conférant le grade de licence sont classés à ce niveau.', 'II', 6, 1),
(7, 'Niveau 7 - Master/Ingénieur', 'Master, Diplôme d''ingénieur, Titre professionnel niveau 7', 'Maîtrise de savoirs très spécialisés, certains au stade le plus avancé des connaissances dans un domaine. Capacité à élaborer et mettre en œuvre des stratégies alternatives pour le développement de l''activité professionnelle dans des contextes complexes, à évaluer les risques et les conséquences de son activité. Les diplômes conférant le grade de master sont classés à ce niveau.', 'I', 7, 1),
(8, 'Niveau 8 - Doctorat', 'Doctorat, Habilitation à diriger des recherches', 'Maîtrise de savoirs à la frontière la plus avancée d''un domaine et à l''interface de plusieurs domaines. Capacité à identifier et résoudre des problèmes complexes et nouveaux impliquant une pluralité de domaines, en mobilisant les connaissances et savoir-faire les plus avancés. Conception et pilotage de projets et processus de recherche et d''innovation.', 'I', 8, 1);

-- =============================================================================
-- TABLE : ref_type_certification
-- Types de certifications professionnelles
-- =============================================================================
TRUNCATE TABLE ref_type_certification;

INSERT INTO ref_type_certification (code, libelle, libelle_abrege, certificateur_type, certificateur_nom, enregistrement_rncp, apprentissage_possible, vae_possible, description, ordre_affichage, actif) VALUES
-- Diplômes d'État (Éducation nationale / Enseignement supérieur)
('CAP', 'Certificat d''aptitude professionnelle', 'CAP', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 1, 1, 'Diplôme national de niveau 3 qui donne une qualification d''ouvrier qualifié ou d''employé qualifié dans un métier déterminé.', 10, 1),
('BEP', 'Brevet d''études professionnelles', 'BEP', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 1, 1, 'Diplôme national de niveau 3 intermédiaire dans le parcours du baccalauréat professionnel.', 11, 1),
('MC', 'Mention complémentaire', 'MC', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 1, 1, 'Diplôme qui permet d''acquérir une spécialisation complémentaire à un CAP ou un baccalauréat professionnel.', 12, 1),
('BP', 'Brevet professionnel', 'BP', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 1, 1, 'Diplôme national de niveau 4 qui atteste l''acquisition d''une haute qualification dans l''exercice d''une activité professionnelle.', 20, 1),
('BAC_PRO', 'Baccalauréat professionnel', 'Bac Pro', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 1, 1, 'Diplôme national de niveau 4 qui atteste d''une qualification professionnelle permettant l''insertion professionnelle ou la poursuite d''études.', 21, 1),
('BAC_TECHNO', 'Baccalauréat technologique', 'Bac Techno', 'ministere', 'Ministère de l''Éducation nationale', 'de_droit', 0, 0, 'Diplôme national de niveau 4 à dominante technologique préparant à la poursuite d''études supérieures.', 22, 1),
('BTS', 'Brevet de technicien supérieur', 'BTS', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 5 (Bac+2) qui permet d''acquérir des compétences dans un domaine professionnel précis.', 30, 1),
('BTSA', 'Brevet de technicien supérieur agricole', 'BTSA', 'ministere', 'Ministère de l''Agriculture et de la Souveraineté alimentaire', 'de_droit', 1, 1, 'Diplôme national de niveau 5 (Bac+2) dans les domaines agricoles, agroalimentaires et environnementaux.', 31, 1),
('DUT', 'Diplôme universitaire de technologie', 'DUT', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 5 (Bac+2) préparé en IUT. Remplacé progressivement par le BUT.', 32, 1),
('BUT', 'Bachelor universitaire de technologie', 'BUT', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 6 (Bac+3) préparé en IUT, remplaçant le DUT depuis 2021.', 40, 1),
('LICENCE', 'Licence', 'Licence', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 6 (Bac+3) délivré par les universités.', 41, 1),
('LP', 'Licence professionnelle', 'LP', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 6 (Bac+3) à finalité professionnelle, préparé en un an après un Bac+2.', 42, 1),
('MASTER', 'Master', 'Master', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 7 (Bac+5) délivré par les universités.', 50, 1),
('INGENIEUR', 'Diplôme d''ingénieur', 'Ingénieur', 'ministere', 'Commission des Titres d''Ingénieur (CTI)', 'de_droit', 1, 1, 'Diplôme de niveau 7 (Bac+5) délivré par les écoles d''ingénieurs habilitées par la CTI.', 51, 1),
('DOCTORAT', 'Doctorat', 'Doctorat', 'ministere', 'Ministère de l''Enseignement supérieur et de la Recherche', 'de_droit', 1, 1, 'Diplôme national de niveau 8 (Bac+8), plus haut grade universitaire.', 60, 1),
-- Diplômes d'État autres ministères
('DE', 'Diplôme d''État', 'DE', 'ministere', 'Ministères (Santé, Affaires sociales, Jeunesse et Sports...)', 'de_droit', 1, 1, 'Diplôme délivré par l''État dans les domaines de la santé, du social, du sport et de l''animation.', 70, 1),
('BPJEPS', 'Brevet professionnel de la jeunesse, de l''éducation populaire et du sport', 'BPJEPS', 'ministere', 'Ministère des Sports', 'de_droit', 1, 1, 'Diplôme de niveau 4 délivré par le ministère des Sports pour l''animation et l''encadrement sportif.', 71, 1),
('DEJEPS', 'Diplôme d''État de la jeunesse, de l''éducation populaire et du sport', 'DEJEPS', 'ministere', 'Ministère des Sports', 'de_droit', 1, 1, 'Diplôme de niveau 5 pour le perfectionnement sportif et l''animation socio-éducative.', 72, 1),
-- Titres professionnels (Ministère du Travail)
('TP', 'Titre professionnel', 'TP', 'ministere', 'Ministère du Travail, du Plein emploi et de l''Insertion', 'de_droit', 1, 1, 'Certification professionnelle délivrée par le ministère du Travail, attestant de compétences professionnelles opérationnelles. Composé de blocs de compétences (CCP).', 80, 1),
-- Titres à finalité professionnelle (Organismes privés/consulaires)
('TFP', 'Titre à finalité professionnelle', 'TFP', 'organisme_prive', 'Organismes de formation privés', 'sur_demande', 1, 1, 'Certification professionnelle délivrée par des organismes privés, enregistrée au RNCP sur demande après instruction par France Compétences.', 90, 1),
('TFP_CCI', 'Titre à finalité professionnelle CCI', 'TFP CCI', 'consulaire', 'CCI France (Chambres de Commerce et d''Industrie)', 'sur_demande', 1, 1, 'Certification délivrée par les Chambres de Commerce et d''Industrie.', 91, 1),
('TFP_CMA', 'Titre à finalité professionnelle CMA', 'TFP CMA', 'consulaire', 'CMA France (Chambres de Métiers et de l''Artisanat)', 'sur_demande', 1, 1, 'Certification délivrée par les Chambres de Métiers et de l''Artisanat.', 92, 1),
('BM', 'Brevet de maîtrise', 'BM', 'consulaire', 'CMA France (Chambres de Métiers et de l''Artisanat)', 'sur_demande', 1, 1, 'Diplôme de niveau 5 délivré par les CMA, sanctionnant une double qualification : chef d''entreprise et formateur.', 93, 1),
-- Certificats de qualification professionnelle (Branches)
('CQP', 'Certificat de qualification professionnelle', 'CQP', 'branche', 'Commissions Paritaires Nationales de l''Emploi (CPNE)', 'sur_demande', 0, 1, 'Certification créée et délivrée par une branche professionnelle, attestant de compétences propres à un métier de la branche.', 100, 1),
('CQPI', 'Certificat de qualification professionnelle inter-branches', 'CQPI', 'branche', 'Plusieurs Commissions Paritaires Nationales de l''Emploi', 'sur_demande', 0, 1, 'Certification inter-branches pour des métiers transversaux à plusieurs secteurs d''activité.', 101, 1),
-- Certifications du Répertoire Spécifique
('HABILITATION', 'Habilitation', 'Habilitation', 'organisme_prive', 'Divers organismes certificateurs', 'non_applicable', 0, 0, 'Certification obligatoire pour l''exercice de certaines activités (ex: CACES, habilitation électrique). Enregistrée au Répertoire Spécifique.', 110, 1),
('CERT_COMP', 'Certification de compétences', 'Cert. Comp.', 'organisme_prive', 'Divers organismes certificateurs', 'non_applicable', 0, 0, 'Certification portant sur des compétences transversales ou complémentaires (ex: CléA, certifications linguistiques). Enregistrée au Répertoire Spécifique.', 111, 1);

-- =============================================================================
-- TABLE : ref_code_nsf
-- Nomenclature des Spécialités de Formation (structure hiérarchique)
-- =============================================================================
TRUNCATE TABLE ref_code_nsf;

-- Niveau 1 : Domaines (4 postes)
INSERT INTO ref_code_nsf (code, libelle, niveau, type_domaine, parent_id, actif) VALUES
('1', 'Domaines disciplinaires', 1, 'disciplinaire', NULL, 1),
('2', 'Domaines technico-professionnels de la production', 1, 'technico_prod', NULL, 1),
('3', 'Domaines technico-professionnels des services', 1, 'technico_services', NULL, 1),
('4', 'Domaines du développement personnel', 1, 'dev_personnel', NULL, 1);

-- Récupération des IDs pour les niveaux suivants
SET @id_dom1 = (SELECT id FROM ref_code_nsf WHERE code = '1');
SET @id_dom2 = (SELECT id FROM ref_code_nsf WHERE code = '2');
SET @id_dom3 = (SELECT id FROM ref_code_nsf WHERE code = '3');
SET @id_dom4 = (SELECT id FROM ref_code_nsf WHERE code = '4');

-- Niveau 2 : Sous-domaines (17 postes)
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, actif) VALUES
-- Domaines disciplinaires
('10', 'Formations générales', 2, @id_dom1, 1),
('11', 'Mathématiques et sciences', 2, @id_dom1, 1),
('12', 'Sciences humaines et droit', 2, @id_dom1, 1),
('13', 'Lettres et arts', 2, @id_dom1, 1),
-- Domaines technico-professionnels de la production
('20', 'Spécialités pluri-technologiques de production', 2, @id_dom2, 1),
('21', 'Agriculture, pêche, forêt et espaces verts', 2, @id_dom2, 1),
('22', 'Transformations', 2, @id_dom2, 1),
('23', 'Génie civil, construction, bois', 2, @id_dom2, 1),
('24', 'Matériaux souples', 2, @id_dom2, 1),
('25', 'Mécanique, électricité, électronique', 2, @id_dom2, 1),
-- Domaines technico-professionnels des services
('30', 'Spécialités plurivalentes des services', 2, @id_dom3, 1),
('31', 'Échanges et gestion', 2, @id_dom3, 1),
('32', 'Communication et information', 2, @id_dom3, 1),
('33', 'Services aux personnes', 2, @id_dom3, 1),
('34', 'Services à la collectivité', 2, @id_dom3, 1),
-- Domaines du développement personnel
('41', 'Capacités individuelles et sociales', 2, @id_dom4, 1),
('42', 'Activités quotidiennes et de loisirs', 2, @id_dom4, 1);

-- Récupération des IDs niveau 2 pour les groupes de spécialités (niveau 3)
SET @id_32 = (SELECT id FROM ref_code_nsf WHERE code = '32');
SET @id_31 = (SELECT id FROM ref_code_nsf WHERE code = '31');
SET @id_25 = (SELECT id FROM ref_code_nsf WHERE code = '25');

-- Niveau 3 : Groupes de spécialités principaux (sélection)
-- Communication et information (32x) - INFORMATIQUE
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, actif) VALUES
('320', 'Spécialités plurivalentes de la communication et de l''information', 3, @id_32, 1),
('321', 'Journalisme, communication', 3, @id_32, 1),
('322', 'Techniques de l''imprimerie et de l''édition', 3, @id_32, 1),
('323', 'Techniques de l''image et du son, métiers connexes du spectacle', 3, @id_32, 1),
('324', 'Secrétariat, bureautique', 3, @id_32, 1),
('325', 'Documentation, bibliothèque, administration des données', 3, @id_32, 1),
('326', 'Informatique, traitement de l''information, réseaux de transmission', 3, @id_32, 1);

-- Échanges et gestion (31x)
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, actif) VALUES
('310', 'Spécialités plurivalentes des échanges et de la gestion', 3, @id_31, 1),
('311', 'Transport, manutention, magasinage', 3, @id_31, 1),
('312', 'Commerce, vente', 3, @id_31, 1),
('313', 'Finances, banque, assurances, immobilier', 3, @id_31, 1),
('314', 'Comptabilité, gestion', 3, @id_31, 1),
('315', 'Ressources humaines, gestion du personnel, gestion de l''emploi', 3, @id_31, 1);

-- Mécanique, électricité, électronique (25x)
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, actif) VALUES
('250', 'Spécialités pluritechnologiques mécanique-électricité', 3, @id_25, 1),
('251', 'Mécanique générale et de précision, usinage', 3, @id_25, 1),
('252', 'Moteurs et mécanique auto', 3, @id_25, 1),
('253', 'Mécanique aéronautique et spatiale', 3, @id_25, 1),
('254', 'Structures métalliques', 3, @id_25, 1),
('255', 'Électricité, électronique', 3, @id_25, 1);

-- Récupération ID du groupe 326 pour le niveau 4
SET @id_326 = (SELECT id FROM ref_code_nsf WHERE code = '326');
SET @id_314 = (SELECT id FROM ref_code_nsf WHERE code = '314');

-- Niveau 4 : Spécialités fines avec fonctions (informatique)
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, code_fonction, libelle_fonction, actif) VALUES
('326m', 'Informatique - Conception', 4, @id_326, 'm', 'Conception d''architectures et de solutions informatiques', 1),
('326n', 'Analyse informatique, conception d''architecture de réseaux', 4, @id_326, 'n', 'Études, développement et conduite de projets', 1),
('326p', 'Informatique, programmation, développement', 4, @id_326, 'p', 'Méthodes, organisation, programmation', 1),
('326r', 'Assistance informatique, maintenance de logiciels et réseaux', 4, @id_326, 'r', 'Contrôle, prévention, maintenance', 1),
('326t', 'Programmation, mise en place de logiciels', 4, @id_326, 't', 'Mise en œuvre, installation, déploiement', 1),
('326u', 'Exploitation informatique', 4, @id_326, 'u', 'Conduite et exploitation des systèmes', 1);

-- Niveau 4 : Spécialités fines avec fonctions (gestion)
INSERT INTO ref_code_nsf (code, libelle, niveau, parent_id, code_fonction, libelle_fonction, actif) VALUES
('314n', 'Études, conseil en gestion', 4, @id_314, 'n', 'Études et projets', 1),
('314p', 'Gestion des organisations', 4, @id_314, 'p', 'Méthodes et organisation', 1),
('314r', 'Contrôle de gestion, audit', 4, @id_314, 'r', 'Contrôle', 1),
('314t', 'Comptabilité, gestion courante', 4, @id_314, 't', 'Production comptable', 1);

-- =============================================================================
-- TABLE : ref_code_rome
-- Répertoire Opérationnel des Métiers et Emplois (ROME 4.0)
-- =============================================================================
TRUNCATE TABLE ref_code_rome;

INSERT INTO ref_code_rome (code, libelle, domaine_code, domaine_libelle, sous_domaine_code, sous_domaine_libelle, definition, version_rome, actif) VALUES
-- Domaine I - Installation et maintenance
('I1401', 'Maintenance informatique et bureautique', 'I', 'Installation et maintenance', '14', 'Maintenance', 'Effectue le dépannage, l''entretien et l''installation d''équipements ou de parcs d''équipements informatiques ou bureautiques, selon les règles de sécurité et la réglementation.', '4.0', 1),

-- Domaine M - Support à l'entreprise (INFORMATIQUE)
('M1801', 'Administration de systèmes d''information', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Administre et assure le fonctionnement et l''exploitation d''un ou plusieurs éléments matériels ou logiciels de l''infrastructure d''un système d''information ou d''un réseau de télécommunications.', '4.0', 1),
('M1802', 'Expertise et support en systèmes d''information', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Apporte un appui technique aux utilisateurs d''un système d''information. Identifie, diagnostique et résout les dysfonctionnements.', '4.0', 1),
('M1803', 'Direction des systèmes d''information', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Définit et met en œuvre la politique informatique de l''entreprise en cohérence avec la stratégie générale.', '4.0', 1),
('M1804', 'Études et développement de réseaux de télécoms', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Étudie, conçoit et développe des solutions techniques de réseaux de télécommunication.', '4.0', 1),
('M1805', 'Études et développement informatique', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Conçoit, développe et met au point un projet d''application informatique, de la phase d''étude à son intégration, pour un client ou une entreprise.', '4.0', 1),
('M1806', 'Conseil et maîtrise d''ouvrage en systèmes d''information', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Conseille la direction informatique, les directions fonctionnelles de l''entreprise dans le cadre de l''élaboration des orientations stratégiques des systèmes d''information.', '4.0', 1),
('M1810', 'Production et exploitation de systèmes d''information', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Assure l''exploitation d''une ou plusieurs applications informatiques au sein d''un centre de production informatique.', '4.0', 1),
('M1811', 'Data science', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Collecte, traite, analyse et valorise des données massives (big data) pour en extraire des informations utiles à la prise de décision.', '4.0', 1),
('M1812', 'Cybersécurité', 'M', 'Support à l''entreprise', '18', 'Systèmes d''information et de télécommunication', 'Analyse les risques et vulnérabilités des systèmes d''information et met en œuvre les mesures de sécurité adaptées.', '4.0', 1),

-- Domaine M - Gestion et comptabilité
('M1203', 'Comptabilité', 'M', 'Support à l''entreprise', '12', 'Gestion et comptabilité', 'Enregistre et centralise les données commerciales, industrielles ou financières d''une structure pour établir des balances de comptes, comptes de résultat, bilans.', '4.0', 1),
('M1204', 'Contrôle de gestion', 'M', 'Support à l''entreprise', '12', 'Gestion et comptabilité', 'Contrôle et analyse la conformité des procédures de gestion de l''entreprise, élabore des indicateurs et tableaux de bord.', '4.0', 1),

-- Domaine M - Ressources humaines
('M1501', 'Assistanat en ressources humaines', 'M', 'Support à l''entreprise', '15', 'Ressources humaines', 'Réalise le suivi administratif de la gestion du personnel selon la législation sociale, la réglementation du travail et la politique des ressources humaines.', '4.0', 1),
('M1502', 'Développement des ressources humaines', 'M', 'Support à l''entreprise', '15', 'Ressources humaines', 'Définit et met en œuvre la politique de management et de gestion des ressources humaines de la structure.', '4.0', 1),

-- Domaine M - Secrétariat et assistanat
('M1602', 'Opérations administratives', 'M', 'Support à l''entreprise', '16', 'Secrétariat et assistanat', 'Réalise des opérations de gestion administrative et comptable selon les procédures de l''organisation.', '4.0', 1),
('M1604', 'Assistanat de direction', 'M', 'Support à l''entreprise', '16', 'Secrétariat et assistanat', 'Assiste un ou plusieurs responsables dans l''organisation de leur travail quotidien.', '4.0', 1),
('M1607', 'Secrétariat', 'M', 'Support à l''entreprise', '16', 'Secrétariat et assistanat', 'Réalise les travaux courants de secrétariat selon les directives données.', '4.0', 1),

-- Domaine M - Marketing et communication
('M1705', 'Marketing', 'M', 'Support à l''entreprise', '17', 'Marketing et communication', 'Définit et met en œuvre la stratégie marketing de l''entreprise.', '4.0', 1),
('M1707', 'Stratégie commerciale', 'M', 'Support à l''entreprise', '17', 'Marketing et communication', 'Définit et met en œuvre la stratégie commerciale d''une entreprise.', '4.0', 1),

-- Domaine D - Commerce, vente et grande distribution
('D1401', 'Assistanat commercial', 'D', 'Commerce, vente et grande distribution', '14', 'Force de vente', 'Réalise le traitement commercial et administratif des commandes des clients dans un objectif de qualité.', '4.0', 1),
('D1402', 'Relation commerciale grands comptes et entreprises', 'D', 'Commerce, vente et grande distribution', '14', 'Force de vente', 'Réalise l''ensemble des activités de prospection, de vente et d''accompagnement de grands comptes ou d''entreprises.', '4.0', 1),
('D1403', 'Relation commerciale auprès de particuliers', 'D', 'Commerce, vente et grande distribution', '14', 'Force de vente', 'Réalise des activités de prospection, de conseil et de vente de produits ou services auprès de particuliers.', '4.0', 1),
('D1406', 'Management en force de vente', 'D', 'Commerce, vente et grande distribution', '14', 'Force de vente', 'Encadre une équipe de commerciaux et met en œuvre la politique commerciale de l''entreprise.', '4.0', 1),

-- Domaine E - Communication, média et multimédia
('E1101', 'Animation de site multimédia', 'E', 'Communication, média et multimédia', '11', 'Conception et gestion de contenu', 'Anime un site internet ou multimédia et assure la mise en ligne des contenus.', '4.0', 1),
('E1104', 'Conception de contenus multimédias', 'E', 'Communication, média et multimédia', '11', 'Conception et gestion de contenu', 'Conçoit et réalise des contenus multimédias (texte, image, son, vidéo) pour différents supports.', '4.0', 1),

-- Domaine K - Services à la personne et à la collectivité
('K2111', 'Formation professionnelle', 'K', 'Services à la personne et à la collectivité', '21', 'Enseignement et formation', 'Réalise des actions de formation auprès d''un public d''adultes ou de jeunes en insertion professionnelle.', '4.0', 1);

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FIN DU SCRIPT
-- =============================================================================
SELECT 'Insertion des données de référence terminée.' AS message;
SELECT COUNT(*) AS nb_niveaux FROM ref_niveau_qualification;
SELECT COUNT(*) AS nb_types_certification FROM ref_type_certification;
SELECT COUNT(*) AS nb_codes_nsf FROM ref_code_nsf;
SELECT COUNT(*) AS nb_codes_rome FROM ref_code_rome;
