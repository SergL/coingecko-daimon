CREATE TABLE public.currancy (
                                 uid uuid NOT NULL PRIMARY KEY,
                                 id serial ,
                                 iso_code varchar(3) NULL,
                                 name varchar(255) NOT NULL,
                                 name_coingecko varchar(255) NULL,
                                 note text NULL
);


INSERT INTO public.currency (id,iso_code,"name",name_coingecko,note)
VALUES (1,'usd','US Dollar','usd','');
INSERT INTO public.currency (id,iso_code,"name",name_coingecko,note)
VALUES (2,'btc','Bitcoin','bitcoin','');
INSERT INTO public.currency (id,iso_code,"name",name_coingecko,note)
VALUES (3,'eth','Ethereum','ethereum','');
INSERT INTO public.currency (id,iso_code,"name",name_coingecko,note)
VALUES (4,'xmr','Monero','monero','');


