-- ============================================
-- DONNÉES DE TEST TAKAFUL
-- 5 associations, 3 sièges/association, 5+ membres/siège
-- Mot de passe pour tous : password
-- ============================================

USE takaful;

SET FOREIGN_KEY_CHECKS = 0;

-- Nettoyer les données existantes (sauf admin)
DELETE FROM assignation;
DELETE FROM candidature_siege;
DELETE FROM demande_aide;
DELETE FROM don;
DELETE FROM mission;
DELETE FROM membre_association;
DELETE FROM siege;
DELETE FROM association;
DELETE FROM membre;

SET FOREIGN_KEY_CHECKS = 1;

-- Hash bcrypt pour "password"
SET @pwd = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ============================================
-- PRÉSIDENTS D'ASSOCIATION (5)
-- ============================================
INSERT INTO membre (id, nom, prenom, email, mot_de_passe, telephone, wilaya, nin, role, statut) VALUES
('pa1', 'Benali',    'Ahmed',    'ahmed.benali@test.dz',    @pwd, '0555100001', 'Alger',       '100000000000000001', 'president_association', 'actif'),
('pa2', 'Hadj',      'Nadia',    'nadia.hadj@test.dz',      @pwd, '0555100002', 'Oran',        '100000000000000002', 'president_association', 'actif'),
('pa3', 'Boudiaf',   'Youcef',   'youcef.boudiaf@test.dz',  @pwd, '0555100003', 'Constantine', '100000000000000003', 'president_association', 'actif'),
('pa4', 'Meziane',   'Amina',    'amina.meziane@test.dz',   @pwd, '0555100004', 'Blida',       '100000000000000004', 'president_association', 'actif'),
('pa5', 'Sahraoui',  'Rachid',   'rachid.sahraoui@test.dz', @pwd, '0555100005', 'Sétif',       '100000000000000005', 'president_association', 'actif');

-- ============================================
-- PRÉSIDENTS DE SIÈGE (15)
-- ============================================
INSERT INTO membre (id, nom, prenom, email, mot_de_passe, telephone, wilaya, nin, role, statut) VALUES
-- Association 1 (Alger) - 3 sièges
('ps01', 'Khelifi',   'Mourad',   'mourad.khelifi@test.dz',   @pwd, '0555200001', 'Alger',       '200000000000000001', 'president_siege', 'actif'),
('ps02', 'Ferhat',    'Leila',    'leila.ferhat@test.dz',     @pwd, '0555200002', 'Blida',       '200000000000000002', 'president_siege', 'actif'),
('ps03', 'Djelloul',  'Karim',    'karim.djelloul@test.dz',   @pwd, '0555200003', 'Tipaza',      '200000000000000003', 'president_siege', 'actif'),
-- Association 2 (Oran) - 3 sièges
('ps04', 'Bouazza',   'Samira',   'samira.bouazza@test.dz',   @pwd, '0555200004', 'Oran',        '200000000000000004', 'president_siege', 'actif'),
('ps05', 'Hamdi',     'Sofiane',  'sofiane.hamdi@test.dz',    @pwd, '0555200005', 'Mostaganem',  '200000000000000005', 'president_siege', 'actif'),
('ps06', 'Tabet',     'Houda',    'houda.tabet@test.dz',      @pwd, '0555200006', 'Tlemcen',     '200000000000000006', 'president_siege', 'actif'),
-- Association 3 (Constantine) - 3 sièges
('ps07', 'Mesbah',    'Amine',    'amine.mesbah@test.dz',     @pwd, '0555200007', 'Constantine', '200000000000000007', 'president_siege', 'actif'),
('ps08', 'Zaidi',     'Meriem',   'meriem.zaidi@test.dz',     @pwd, '0555200008', 'Annaba',      '200000000000000008', 'president_siege', 'actif'),
('ps09', 'Bouzid',    'Walid',    'walid.bouzid@test.dz',     @pwd, '0555200009', 'Batna',       '200000000000000009', 'president_siege', 'actif'),
-- Association 4 (Blida) - 3 sièges
('ps10', 'Cherif',    'Yasmine',  'yasmine.cherif@test.dz',   @pwd, '0555200010', 'Blida',       '200000000000000010', 'president_siege', 'actif'),
('ps11', 'Larbi',     'Mehdi',    'mehdi.larbi@test.dz',      @pwd, '0555200011', 'Médéa',       '200000000000000011', 'president_siege', 'actif'),
('ps12', 'Rahmani',   'Dalila',   'dalila.rahmani@test.dz',   @pwd, '0555200012', 'Djelfa',      '200000000000000012', 'president_siege', 'actif'),
-- Association 5 (Sétif) - 3 sièges
('ps13', 'Haddad',    'Farid',    'farid.haddad@test.dz',     @pwd, '0555200013', 'Sétif',       '200000000000000013', 'president_siege', 'actif'),
('ps14', 'Slimani',   'Souad',    'souad.slimani@test.dz',    @pwd, '0555200014', 'Béjaïa',      '200000000000000014', 'president_siege', 'actif'),
('ps15', 'Aissaoui',  'Bilal',    'bilal.aissaoui@test.dz',   @pwd, '0555200015', 'Bordj Bou Arréridj', '200000000000000015', 'president_siege', 'actif');

-- ============================================
-- MEMBRES (75 - 5 par siège)
-- ============================================
INSERT INTO membre (id, nom, prenom, email, mot_de_passe, telephone, wilaya, nin, role, statut) VALUES
-- Siège 1 (Alger - Assoc 1)
('mb01', 'Amrani',    'Yassine',  'yassine.amrani@test.dz',   @pwd, '0555300001', 'Alger',       '300000000000000001', 'membre_association', 'actif'),
('mb02', 'Benaissa',  'Aicha',    'aicha.benaissa@test.dz',   @pwd, '0555300002', 'Alger',       '300000000000000002', 'membre_association', 'actif'),
('mb03', 'Cherifi',   'Omar',     'omar.cherifi@test.dz',     @pwd, '0555300003', 'Alger',       '300000000000000003', 'membre_association', 'actif'),
('mb04', 'Djamel',    'Fatima',   'fatima.djamel@test.dz',    @pwd, '0555300004', 'Alger',       '300000000000000004', 'membre_association', 'actif'),
('mb05', 'El Hadi',   'Mohamed',  'mohamed.elhadi@test.dz',   @pwd, '0555300005', 'Alger',       '300000000000000005', 'membre_association', 'actif'),
-- Siège 2 (Blida - Assoc 1)
('mb06', 'Ferhi',     'Salima',   'salima.ferhi@test.dz',     @pwd, '0555300006', 'Blida',       '300000000000000006', 'membre_association', 'actif'),
('mb07', 'Guendouz',  'Adel',     'adel.guendouz@test.dz',    @pwd, '0555300007', 'Blida',       '300000000000000007', 'membre_association', 'actif'),
('mb08', 'Hamidou',   'Nawal',    'nawal.hamidou@test.dz',    @pwd, '0555300008', 'Blida',       '300000000000000008', 'membre_association', 'actif'),
('mb09', 'Ibrahim',   'Samir',    'samir.ibrahim@test.dz',    @pwd, '0555300009', 'Blida',       '300000000000000009', 'membre_association', 'actif'),
('mb10', 'Jalil',     'Karima',   'karima.jalil@test.dz',     @pwd, '0555300010', 'Blida',       '300000000000000010', 'membre_association', 'actif'),
-- Siège 3 (Tipaza - Assoc 1)
('mb11', 'Kaci',      'Djamel',   'djamel.kaci@test.dz',      @pwd, '0555300011', 'Tipaza',      '300000000000000011', 'membre_association', 'actif'),
('mb12', 'Lakhdari',  'Sabrina',  'sabrina.lakhdari@test.dz', @pwd, '0555300012', 'Tipaza',      '300000000000000012', 'membre_association', 'actif'),
('mb13', 'Makhlouf',  'Redouane', 'redouane.makhlouf@test.dz',@pwd, '0555300013', 'Tipaza',      '300000000000000013', 'membre_association', 'actif'),
('mb14', 'Nasri',     'Lamia',    'lamia.nasri@test.dz',      @pwd, '0555300014', 'Tipaza',      '300000000000000014', 'membre_association', 'actif'),
('mb15', 'Ouahab',    'Tarek',    'tarek.ouahab@test.dz',     @pwd, '0555300015', 'Tipaza',      '300000000000000015', 'membre_association', 'actif'),
-- Siège 4 (Oran - Assoc 2)
('mb16', 'Benmoussa', 'Hanane',   'hanane.benmoussa@test.dz', @pwd, '0555300016', 'Oran',        '300000000000000016', 'membre_association', 'actif'),
('mb17', 'Chaabane',  'Nassim',   'nassim.chaabane@test.dz',  @pwd, '0555300017', 'Oran',        '300000000000000017', 'membre_association', 'actif'),
('mb18', 'Dahmani',   'Rania',    'rania.dahmani@test.dz',    @pwd, '0555300018', 'Oran',        '300000000000000018', 'membre_association', 'actif'),
('mb19', 'Essaid',    'Khaled',   'khaled.essaid@test.dz',    @pwd, '0555300019', 'Oran',        '300000000000000019', 'membre_association', 'actif'),
('mb20', 'Fekhar',    'Amira',    'amira.fekhar@test.dz',     @pwd, '0555300020', 'Oran',        '300000000000000020', 'membre_association', 'actif'),
-- Siège 5 (Mostaganem - Assoc 2)
('mb21', 'Ghali',     'Noureddine','noureddine.ghali@test.dz',@pwd, '0555300021', 'Mostaganem',  '300000000000000021', 'membre_association', 'actif'),
('mb22', 'Henni',     'Warda',    'warda.henni@test.dz',      @pwd, '0555300022', 'Mostaganem',  '300000000000000022', 'membre_association', 'actif'),
('mb23', 'Issad',     'Abdelkader','abdelkader.issad@test.dz',@pwd, '0555300023', 'Mostaganem',  '300000000000000023', 'membre_association', 'actif'),
('mb24', 'Kaddour',   'Sihem',    'sihem.kaddour@test.dz',    @pwd, '0555300024', 'Mostaganem',  '300000000000000024', 'membre_association', 'actif'),
('mb25', 'Lounis',    'Fares',    'fares.lounis@test.dz',     @pwd, '0555300025', 'Mostaganem',  '300000000000000025', 'membre_association', 'actif'),
-- Siège 6 (Tlemcen - Assoc 2)
('mb26', 'Merad',     'Naima',    'naima.merad@test.dz',      @pwd, '0555300026', 'Tlemcen',     '300000000000000026', 'membre_association', 'actif'),
('mb27', 'Nadji',     'Hakim',    'hakim.nadji@test.dz',      @pwd, '0555300027', 'Tlemcen',     '300000000000000027', 'membre_association', 'actif'),
('mb28', 'Ouargli',   'Zineb',    'zineb.ouargli@test.dz',    @pwd, '0555300028', 'Tlemcen',     '300000000000000028', 'membre_association', 'actif'),
('mb29', 'Rezki',     'Ismail',   'ismail.rezki@test.dz',     @pwd, '0555300029', 'Tlemcen',     '300000000000000029', 'membre_association', 'actif'),
('mb30', 'Seghir',    'Asma',     'asma.seghir@test.dz',      @pwd, '0555300030', 'Tlemcen',     '300000000000000030', 'membre_association', 'actif'),
-- Siège 7 (Constantine - Assoc 3)
('mb31', 'Touati',    'Riad',     'riad.touati@test.dz',      @pwd, '0555300031', 'Constantine', '300000000000000031', 'membre_association', 'actif'),
('mb32', 'Boudjenah', 'Imane',    'imane.boudjenah@test.dz',  @pwd, '0555300032', 'Constantine', '300000000000000032', 'membre_association', 'actif'),
('mb33', 'Chabane',   'Lotfi',    'lotfi.chabane@test.dz',    @pwd, '0555300033', 'Constantine', '300000000000000033', 'membre_association', 'actif'),
('mb34', 'Derradji',  'Nabila',   'nabila.derradji@test.dz',  @pwd, '0555300034', 'Constantine', '300000000000000034', 'membre_association', 'actif'),
('mb35', 'Ferhoune',  'Zakaria',  'zakaria.ferhoune@test.dz', @pwd, '0555300035', 'Constantine', '300000000000000035', 'membre_association', 'actif'),
-- Siège 8 (Annaba - Assoc 3)
('mb36', 'Gasmi',     'Djamila',  'djamila.gasmi@test.dz',    @pwd, '0555300036', 'Annaba',      '300000000000000036', 'membre_association', 'actif'),
('mb37', 'Hachemi',   'Yazid',    'yazid.hachemi@test.dz',    @pwd, '0555300037', 'Annaba',      '300000000000000037', 'membre_association', 'actif'),
('mb38', 'Kara',      'Noria',    'noria.kara@test.dz',       @pwd, '0555300038', 'Annaba',      '300000000000000038', 'membre_association', 'actif'),
('mb39', 'Maachi',    'Amine',    'amine.maachi@test.dz',     @pwd, '0555300039', 'Annaba',      '300000000000000039', 'membre_association', 'actif'),
('mb40', 'Nemiri',    'Chahinez', 'chahinez.nemiri@test.dz',  @pwd, '0555300040', 'Annaba',      '300000000000000040', 'membre_association', 'actif'),
-- Siège 9 (Batna - Assoc 3)
('mb41', 'Oukil',     'Hamza',    'hamza.oukil@test.dz',      @pwd, '0555300041', 'Batna',       '300000000000000041', 'membre_association', 'actif'),
('mb42', 'Rahal',     'Soumia',   'soumia.rahal@test.dz',     @pwd, '0555300042', 'Batna',       '300000000000000042', 'membre_association', 'actif'),
('mb43', 'Selami',    'Abderahim','abderahim.selami@test.dz', @pwd, '0555300043', 'Batna',       '300000000000000043', 'membre_association', 'actif'),
('mb44', 'Talbi',     'Hafsa',    'hafsa.talbi@test.dz',      @pwd, '0555300044', 'Batna',       '300000000000000044', 'membre_association', 'actif'),
('mb45', 'Yahiaoui',  'Moussa',   'moussa.yahiaoui@test.dz',  @pwd, '0555300045', 'Batna',       '300000000000000045', 'membre_association', 'actif'),
-- Siège 10 (Blida - Assoc 4)
('mb46', 'Belarbi',   'Ikram',    'ikram.belarbi@test.dz',    @pwd, '0555300046', 'Blida',       '300000000000000046', 'membre_association', 'actif'),
('mb47', 'Djerfi',    'Hamid',    'hamid.djerfi@test.dz',     @pwd, '0555300047', 'Blida',       '300000000000000047', 'membre_association', 'actif'),
('mb48', 'Ghezali',   'Lydia',    'lydia.ghezali@test.dz',    @pwd, '0555300048', 'Blida',       '300000000000000048', 'membre_association', 'actif'),
('mb49', 'Kechida',   'Fouad',    'fouad.kechida@test.dz',    @pwd, '0555300049', 'Blida',       '300000000000000049', 'membre_association', 'actif'),
('mb50', 'Mansouri',  'Khadidja', 'khadidja.mansouri@test.dz',@pwd, '0555300050', 'Blida',       '300000000000000050', 'membre_association', 'actif'),
-- Siège 11 (Médéa - Assoc 4)
('mb51', 'Oussedik',  'Réda',     'reda.oussedik@test.dz',    @pwd, '0555300051', 'Médéa',       '300000000000000051', 'membre_association', 'actif'),
('mb52', 'Saidani',   'Malika',   'malika.saidani@test.dz',   @pwd, '0555300052', 'Médéa',       '300000000000000052', 'membre_association', 'actif'),
('mb53', 'Tounsi',    'Nabil',    'nabil.tounsi@test.dz',     @pwd, '0555300053', 'Médéa',       '300000000000000053', 'membre_association', 'actif'),
('mb54', 'Zidane',    'Farida',   'farida.zidane@test.dz',    @pwd, '0555300054', 'Médéa',       '300000000000000054', 'membre_association', 'actif'),
('mb55', 'Abdi',      'Hichem',   'hichem.abdi@test.dz',      @pwd, '0555300055', 'Médéa',       '300000000000000055', 'membre_association', 'actif'),
-- Siège 12 (Djelfa - Assoc 4)
('mb56', 'Boukhalfa', 'Samia',    'samia.boukhalfa@test.dz',  @pwd, '0555300056', 'Djelfa',      '300000000000000056', 'membre_association', 'actif'),
('mb57', 'Dehimi',    'Rachid',   'rachid.dehimi@test.dz',    @pwd, '0555300057', 'Djelfa',      '300000000000000057', 'membre_association', 'actif'),
('mb58', 'Foudil',    'Nassima',  'nassima.foudil@test.dz',   @pwd, '0555300058', 'Djelfa',      '300000000000000058', 'membre_association', 'actif'),
('mb59', 'Hadji',     'Toufik',   'toufik.hadji@test.dz',     @pwd, '0555300059', 'Djelfa',      '300000000000000059', 'membre_association', 'actif'),
('mb60', 'Khelladi',  'Amel',     'amel.khelladi@test.dz',    @pwd, '0555300060', 'Djelfa',      '300000000000000060', 'membre_association', 'actif'),
-- Siège 13 (Sétif - Assoc 5)
('mb61', 'Laoufi',    'Salah',    'salah.laoufi@test.dz',     @pwd, '0555300061', 'Sétif',       '300000000000000061', 'membre_association', 'actif'),
('mb62', 'Mebarki',   'Souhila',  'souhila.mebarki@test.dz',  @pwd, '0555300062', 'Sétif',       '300000000000000062', 'membre_association', 'actif'),
('mb63', 'Oudjit',    'Raouf',    'raouf.oudjit@test.dz',     @pwd, '0555300063', 'Sétif',       '300000000000000063', 'membre_association', 'actif'),
('mb64', 'Rabhi',     'Linda',    'linda.rabhi@test.dz',      @pwd, '0555300064', 'Sétif',       '300000000000000064', 'membre_association', 'actif'),
('mb65', 'Sidhoum',   'Abdelghani','abdelghani.sidhoum@test.dz',@pwd,'0555300065','Sétif',       '300000000000000065', 'membre_association', 'actif'),
-- Siège 14 (Béjaïa - Assoc 5)
('mb66', 'Tayeb',     'Hassina',  'hassina.tayeb@test.dz',    @pwd, '0555300066', 'Béjaïa',      '300000000000000066', 'membre_association', 'actif'),
('mb67', 'Yahia',     'Djaafar',  'djaafar.yahia@test.dz',    @pwd, '0555300067', 'Béjaïa',      '300000000000000067', 'membre_association', 'actif'),
('mb68', 'Ait Kaci',  'Sabah',    'sabah.aitkaci@test.dz',    @pwd, '0555300068', 'Béjaïa',      '300000000000000068', 'membre_association', 'actif'),
('mb69', 'Belkacem',  'Nadir',    'nadir.belkacem@test.dz',   @pwd, '0555300069', 'Béjaïa',      '300000000000000069', 'membre_association', 'actif'),
('mb70', 'Chikhi',    'Ghania',   'ghania.chikhi@test.dz',    @pwd, '0555300070', 'Béjaïa',      '300000000000000070', 'membre_association', 'actif'),
-- Siège 15 (BBA - Assoc 5)
('mb71', 'Drif',      'Madjid',   'madjid.drif@test.dz',      @pwd, '0555300071', 'Bordj Bou Arréridj', '300000000000000071', 'membre_association', 'actif'),
('mb72', 'Fellah',    'Razika',   'razika.fellah@test.dz',    @pwd, '0555300072', 'Bordj Bou Arréridj', '300000000000000072', 'membre_association', 'actif'),
('mb73', 'Guettaf',   'Hocine',   'hocine.guettaf@test.dz',   @pwd, '0555300073', 'Bordj Bou Arréridj', '300000000000000073', 'membre_association', 'actif'),
('mb74', 'Hammoudi',  'Nacera',   'nacera.hammoudi@test.dz',  @pwd, '0555300074', 'Bordj Bou Arréridj', '300000000000000074', 'membre_association', 'actif'),
('mb75', 'Khelil',    'Mounir',   'mounir.khelil@test.dz',    @pwd, '0555300075', 'Bordj Bou Arréridj', '300000000000000075', 'membre_association', 'actif');

-- ============================================
-- ASSOCIATIONS (5)
-- ============================================
INSERT INTO association (id, nom, description, president_id, statut, date_creation) VALUES
('a1', 'Nour El Ihsan',
 'Association humanitaire dédiée à l''aide aux familles démunies dans la région centre. Nos actions couvrent la distribution alimentaire, le soutien scolaire et l''accompagnement social.',
 'pa1', 'active', '2024-01-15 10:00:00'),

('a2', 'El Baraka',
 'Association caritative spécialisée dans l''aide médicale et l''accompagnement des malades chroniques dans la région ouest. Nous organisons des caravanes médicales régulières.',
 'pa2', 'active', '2024-03-20 14:30:00'),

('a3', 'Rahma',
 'Association d''entraide sociale pour les orphelins et les veuves dans la région est. Programmes de parrainage et de formation professionnelle.',
 'pa3', 'active', '2024-05-10 09:00:00'),

('a4', 'El Amel',
 'Association de solidarité pour le soutien aux personnes âgées et handicapées. Organisation de visites à domicile et distribution de matériel médical.',
 'pa4', 'active', '2024-07-01 11:00:00'),

('a5', 'Tawassol',
 'Association de développement communautaire axée sur l''alphabétisation, la formation professionnelle des jeunes et l''insertion économique des familles vulnérables.',
 'pa5', 'active', '2024-09-15 08:30:00');

-- ============================================
-- SIÈGES (15 - 3 par association)
-- ============================================
INSERT INTO siege (id, nom, wilaya, adresse, association_id, president_siege_id, statut, date_creation) VALUES
-- Association 1 : Nour El Ihsan
('s01', 'Siège Central Alger',     'Alger',   '25 Rue Didouche Mourad, Alger Centre',       'a1', 'ps01', 'actif', '2024-02-01 10:00:00'),
('s02', 'Siège Blida',             'Blida',   '10 Boulevard Larbi Tébessi, Blida',          'a1', 'ps02', 'actif', '2024-02-15 10:00:00'),
('s03', 'Siège Tipaza',            'Tipaza',  '05 Rue des Frères Bouadou, Tipaza',          'a1', 'ps03', 'actif', '2024-03-01 10:00:00'),
-- Association 2 : El Baraka
('s04', 'Siège Central Oran',      'Oran',    '18 Rue Larbi Ben M''hidi, Oran',              'a2', 'ps04', 'actif', '2024-04-01 10:00:00'),
('s05', 'Siège Mostaganem',        'Mostaganem','12 Avenue de l''Indépendance, Mostaganem',  'a2', 'ps05', 'actif', '2024-04-15 10:00:00'),
('s06', 'Siège Tlemcen',           'Tlemcen', '08 Rue Commandant Djaber, Tlemcen',          'a2', 'ps06', 'actif', '2024-05-01 10:00:00'),
-- Association 3 : Rahma
('s07', 'Siège Central Constantine','Constantine','30 Rue Abane Ramdane, Constantine',       'a3', 'ps07', 'actif', '2024-06-01 10:00:00'),
('s08', 'Siège Annaba',            'Annaba',  '15 Cours de la Révolution, Annaba',          'a3', 'ps08', 'actif', '2024-06-15 10:00:00'),
('s09', 'Siège Batna',             'Batna',   '22 Rue 1er Novembre, Batna',                 'a3', 'ps09', 'actif', '2024-07-01 10:00:00'),
-- Association 4 : El Amel
('s10', 'Siège Central Blida',     'Blida',   '45 Rue Mohamed Boudiaf, Blida',              'a4', 'ps10', 'actif', '2024-08-01 10:00:00'),
('s11', 'Siège Médéa',             'Médéa',   '07 Place des Martyrs, Médéa',                'a4', 'ps11', 'actif', '2024-08-15 10:00:00'),
('s12', 'Siège Djelfa',            'Djelfa',  '33 Boulevard Emir Abdelkader, Djelfa',       'a4', 'ps12', 'actif', '2024-09-01 10:00:00'),
-- Association 5 : Tawassol
('s13', 'Siège Central Sétif',     'Sétif',   '20 Rue du 8 Mai 1945, Sétif',               'a5', 'ps13', 'actif', '2024-10-01 10:00:00'),
('s14', 'Siège Béjaïa',            'Béjaïa',  '11 Rue Amirouche, Béjaïa',                  'a5', 'ps14', 'actif', '2024-10-15 10:00:00'),
('s15', 'Siège Bordj Bou Arréridj','Bordj Bou Arréridj','14 Rue Si El Haouès, BBA',        'a5', 'ps15', 'actif', '2024-11-01 10:00:00');

-- ============================================
-- MEMBRE_ASSOCIATION : Présidents d'association
-- ============================================
INSERT INTO membre_association (id, membre_id, association_id, siege_id, date_adhesion, statut) VALUES
('ma_pa1', 'pa1', 'a1', NULL, '2024-01-15', 'actif'),
('ma_pa2', 'pa2', 'a2', NULL, '2024-03-20', 'actif'),
('ma_pa3', 'pa3', 'a3', NULL, '2024-05-10', 'actif'),
('ma_pa4', 'pa4', 'a4', NULL, '2024-07-01', 'actif'),
('ma_pa5', 'pa5', 'a5', NULL, '2024-09-15', 'actif');

-- ============================================
-- MEMBRE_ASSOCIATION : Présidents de siège
-- ============================================
INSERT INTO membre_association (id, membre_id, association_id, siege_id, date_adhesion, statut) VALUES
('ma_ps01', 'ps01', 'a1', 's01', '2024-02-01', 'actif'),
('ma_ps02', 'ps02', 'a1', 's02', '2024-02-15', 'actif'),
('ma_ps03', 'ps03', 'a1', 's03', '2024-03-01', 'actif'),
('ma_ps04', 'ps04', 'a2', 's04', '2024-04-01', 'actif'),
('ma_ps05', 'ps05', 'a2', 's05', '2024-04-15', 'actif'),
('ma_ps06', 'ps06', 'a2', 's06', '2024-05-01', 'actif'),
('ma_ps07', 'ps07', 'a3', 's07', '2024-06-01', 'actif'),
('ma_ps08', 'ps08', 'a3', 's08', '2024-06-15', 'actif'),
('ma_ps09', 'ps09', 'a3', 's09', '2024-07-01', 'actif'),
('ma_ps10', 'ps10', 'a4', 's10', '2024-08-01', 'actif'),
('ma_ps11', 'ps11', 'a4', 's11', '2024-08-15', 'actif'),
('ma_ps12', 'ps12', 'a4', 's12', '2024-09-01', 'actif'),
('ma_ps13', 'ps13', 'a5', 's13', '2024-10-01', 'actif'),
('ma_ps14', 'ps14', 'a5', 's14', '2024-10-15', 'actif'),
('ma_ps15', 'ps15', 'a5', 's15', '2024-11-01', 'actif');

-- ============================================
-- MEMBRE_ASSOCIATION : Membres (5 par siège)
-- ============================================
INSERT INTO membre_association (id, membre_id, association_id, siege_id, date_adhesion, statut) VALUES
-- Siège 1 (s01 - Alger)
('ma_mb01', 'mb01', 'a1', 's01', '2024-03-10', 'actif'),
('ma_mb02', 'mb02', 'a1', 's01', '2024-03-12', 'actif'),
('ma_mb03', 'mb03', 'a1', 's01', '2024-03-15', 'actif'),
('ma_mb04', 'mb04', 'a1', 's01', '2024-03-18', 'actif'),
('ma_mb05', 'mb05', 'a1', 's01', '2024-03-20', 'actif'),
-- Siège 2 (s02 - Blida)
('ma_mb06', 'mb06', 'a1', 's02', '2024-03-22', 'actif'),
('ma_mb07', 'mb07', 'a1', 's02', '2024-03-25', 'actif'),
('ma_mb08', 'mb08', 'a1', 's02', '2024-04-01', 'actif'),
('ma_mb09', 'mb09', 'a1', 's02', '2024-04-05', 'actif'),
('ma_mb10', 'mb10', 'a1', 's02', '2024-04-08', 'actif'),
-- Siège 3 (s03 - Tipaza)
('ma_mb11', 'mb11', 'a1', 's03', '2024-04-10', 'actif'),
('ma_mb12', 'mb12', 'a1', 's03', '2024-04-12', 'actif'),
('ma_mb13', 'mb13', 'a1', 's03', '2024-04-15', 'actif'),
('ma_mb14', 'mb14', 'a1', 's03', '2024-04-18', 'actif'),
('ma_mb15', 'mb15', 'a1', 's03', '2024-04-20', 'actif'),
-- Siège 4 (s04 - Oran)
('ma_mb16', 'mb16', 'a2', 's04', '2024-05-01', 'actif'),
('ma_mb17', 'mb17', 'a2', 's04', '2024-05-03', 'actif'),
('ma_mb18', 'mb18', 'a2', 's04', '2024-05-05', 'actif'),
('ma_mb19', 'mb19', 'a2', 's04', '2024-05-08', 'actif'),
('ma_mb20', 'mb20', 'a2', 's04', '2024-05-10', 'actif'),
-- Siège 5 (s05 - Mostaganem)
('ma_mb21', 'mb21', 'a2', 's05', '2024-05-12', 'actif'),
('ma_mb22', 'mb22', 'a2', 's05', '2024-05-15', 'actif'),
('ma_mb23', 'mb23', 'a2', 's05', '2024-05-18', 'actif'),
('ma_mb24', 'mb24', 'a2', 's05', '2024-05-20', 'actif'),
('ma_mb25', 'mb25', 'a2', 's05', '2024-05-22', 'actif'),
-- Siège 6 (s06 - Tlemcen)
('ma_mb26', 'mb26', 'a2', 's06', '2024-06-01', 'actif'),
('ma_mb27', 'mb27', 'a2', 's06', '2024-06-03', 'actif'),
('ma_mb28', 'mb28', 'a2', 's06', '2024-06-05', 'actif'),
('ma_mb29', 'mb29', 'a2', 's06', '2024-06-08', 'actif'),
('ma_mb30', 'mb30', 'a2', 's06', '2024-06-10', 'actif'),
-- Siège 7 (s07 - Constantine)
('ma_mb31', 'mb31', 'a3', 's07', '2024-07-01', 'actif'),
('ma_mb32', 'mb32', 'a3', 's07', '2024-07-03', 'actif'),
('ma_mb33', 'mb33', 'a3', 's07', '2024-07-05', 'actif'),
('ma_mb34', 'mb34', 'a3', 's07', '2024-07-08', 'actif'),
('ma_mb35', 'mb35', 'a3', 's07', '2024-07-10', 'actif'),
-- Siège 8 (s08 - Annaba)
('ma_mb36', 'mb36', 'a3', 's08', '2024-07-12', 'actif'),
('ma_mb37', 'mb37', 'a3', 's08', '2024-07-15', 'actif'),
('ma_mb38', 'mb38', 'a3', 's08', '2024-07-18', 'actif'),
('ma_mb39', 'mb39', 'a3', 's08', '2024-07-20', 'actif'),
('ma_mb40', 'mb40', 'a3', 's08', '2024-07-22', 'actif'),
-- Siège 9 (s09 - Batna)
('ma_mb41', 'mb41', 'a3', 's09', '2024-08-01', 'actif'),
('ma_mb42', 'mb42', 'a3', 's09', '2024-08-03', 'actif'),
('ma_mb43', 'mb43', 'a3', 's09', '2024-08-05', 'actif'),
('ma_mb44', 'mb44', 'a3', 's09', '2024-08-08', 'actif'),
('ma_mb45', 'mb45', 'a3', 's09', '2024-08-10', 'actif'),
-- Siège 10 (s10 - Blida)
('ma_mb46', 'mb46', 'a4', 's10', '2024-09-01', 'actif'),
('ma_mb47', 'mb47', 'a4', 's10', '2024-09-03', 'actif'),
('ma_mb48', 'mb48', 'a4', 's10', '2024-09-05', 'actif'),
('ma_mb49', 'mb49', 'a4', 's10', '2024-09-08', 'actif'),
('ma_mb50', 'mb50', 'a4', 's10', '2024-09-10', 'actif'),
-- Siège 11 (s11 - Médéa)
('ma_mb51', 'mb51', 'a4', 's11', '2024-09-12', 'actif'),
('ma_mb52', 'mb52', 'a4', 's11', '2024-09-15', 'actif'),
('ma_mb53', 'mb53', 'a4', 's11', '2024-09-18', 'actif'),
('ma_mb54', 'mb54', 'a4', 's11', '2024-09-20', 'actif'),
('ma_mb55', 'mb55', 'a4', 's11', '2024-09-22', 'actif'),
-- Siège 12 (s12 - Djelfa)
('ma_mb56', 'mb56', 'a4', 's12', '2024-10-01', 'actif'),
('ma_mb57', 'mb57', 'a4', 's12', '2024-10-03', 'actif'),
('ma_mb58', 'mb58', 'a4', 's12', '2024-10-05', 'actif'),
('ma_mb59', 'mb59', 'a4', 's12', '2024-10-08', 'actif'),
('ma_mb60', 'mb60', 'a4', 's12', '2024-10-10', 'actif'),
-- Siège 13 (s13 - Sétif)
('ma_mb61', 'mb61', 'a5', 's13', '2024-11-01', 'actif'),
('ma_mb62', 'mb62', 'a5', 's13', '2024-11-03', 'actif'),
('ma_mb63', 'mb63', 'a5', 's13', '2024-11-05', 'actif'),
('ma_mb64', 'mb64', 'a5', 's13', '2024-11-08', 'actif'),
('ma_mb65', 'mb65', 'a5', 's13', '2024-11-10', 'actif'),
-- Siège 14 (s14 - Béjaïa)
('ma_mb66', 'mb66', 'a5', 's14', '2024-11-12', 'actif'),
('ma_mb67', 'mb67', 'a5', 's14', '2024-11-15', 'actif'),
('ma_mb68', 'mb68', 'a5', 's14', '2024-11-18', 'actif'),
('ma_mb69', 'mb69', 'a5', 's14', '2024-11-20', 'actif'),
('ma_mb70', 'mb70', 'a5', 's14', '2024-11-22', 'actif'),
-- Siège 15 (s15 - BBA)
('ma_mb71', 'mb71', 'a5', 's15', '2024-12-01', 'actif'),
('ma_mb72', 'mb72', 'a5', 's15', '2024-12-03', 'actif'),
('ma_mb73', 'mb73', 'a5', 's15', '2024-12-05', 'actif'),
('ma_mb74', 'mb74', 'a5', 's15', '2024-12-08', 'actif'),
('ma_mb75', 'mb75', 'a5', 's15', '2024-12-10', 'actif');

-- ============================================
-- VÉRIFICATION
-- ============================================
SELECT 'Membres créés' AS info, COUNT(*) AS total FROM membre;
SELECT 'Associations créées' AS info, COUNT(*) AS total FROM association;
SELECT 'Sièges créés' AS info, COUNT(*) AS total FROM siege;
SELECT 'Liens membre_association' AS info, COUNT(*) AS total FROM membre_association;
SELECT a.nom AS association_name, s.nom AS siege_name, COUNT(ma.id) AS nb_membres
FROM association a
JOIN siege s ON s.association_id = a.id
LEFT JOIN membre_association ma ON ma.siege_id = s.id AND ma.statut = 'actif' AND ma.membre_id NOT IN (SELECT COALESCE(president_siege_id,'') FROM siege)
GROUP BY a.nom, s.nom
ORDER BY a.nom, s.nom;
