CREATE TABLE Clienti (
 cod_client NUMBER(37) PRIMARY KEY,
 fullname VARCHAR(20) NOT NULL,
 username VARCHAR2(50) NOT NULL UNIQUE,
 password VARCHAR2(255) NOT NULL
);

create sequence pk_clienti START WITH 1 INCREMENT BY 1;

CREATE TABLE Cos_Comanda(
    cod_cos_comanda NUMBER(4) PRIMARY KEY,
    cantitate NUMBER(4),
    pret_total NUMBER(4),
    cod_client NUMBER(4) UNIQUE,
    FOREIGN KEY (cod_client) REFERENCES Clienti(cod_client)
);

create sequence pk_cos_comanda START WITH 1 INCREMENT BY 1;

CREATE TABLE Carti (
 cod_carte NUMBER(4) PRIMARY KEY,
 titlu VARCHAR(20),
 autor VARCHAR(20),
 pret NUMBER(4),
 stoc NUMBER(4),
 cod_cos_comanda NUMBER,
 FOREIGN KEY (cod_cos_comanda) REFERENCES Cos_Comanda(cod_cos_comanda),
 UNIQUE (titlu, autor, pret)
);

create sequence pk_carti START WITH 1 INCREMENT BY 1;

CREATE TABLE Comenzi(
    cod_carte NUMBER(4),
    cod_client NUMBER(4),
    numar_comanda NUMBER(4),
    PRIMARY KEY (cod_carte, cod_client, numar_comanda),
    FOREIGN KEY (cod_carte) REFERENCES Carti(cod_carte),
    FOREIGN KEY (cod_client) REFERENCES Clienti(cod_client)
);

create sequence pk_comenzi START WITH 1 INCREMENT BY 1;


CREATE TABLE Istoric_Achizitii (
    idIstoric NUMBER(4) PRIMARY KEY,
    cod_client NUMBER(4),
    cod_carte NUMBER(4),
    cantitate NUMBER(4),
    data_achizitie DATE DEFAULT SYSDATE,
    FOREIGN KEY (cod_client) REFERENCES Clienti(cod_client),
    FOREIGN KEY (cod_carte) REFERENCES Carti(cod_carte)
);

Describe Istoric_Achizitii;
Alter Table Istoric_Achizitii add (ID_COMANDA NUMBER);
Delete From istoric_achizitii;


CREATE SEQUENCE pk_Istoric_Achizitii START WITH 1 INCREMENT BY 1;

SELECT * FROM clienti;
SELECT * FROM carti;
SELECT * FROM cos_comanda;
SELECT * FROM comenzi;
SELECT * FROM Istoric_Achizitii;

DELETE FROM Istoric_Achizitii;
TRUNCATE TABLE Istoric_Achizitii;

CREATE SEQUENCE pk_Orders START WITH 1 INCREMENT BY 1;

