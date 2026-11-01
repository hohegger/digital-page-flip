CREATE TABLE tt_content (
	tx_digitalpageflip_flipbook int(11) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_digitalpageflip_domain_model_flipbook (
	title varchar(255) DEFAULT '' NOT NULL,
	description text,
	pdf_file int(11) unsigned DEFAULT '0' NOT NULL,
	pages int(11) unsigned DEFAULT '0' NOT NULL,
	page_count int(11) DEFAULT '0' NOT NULL,
	conversion_status int(11) DEFAULT '0' NOT NULL,
	valid_from int(11) unsigned DEFAULT '0' NOT NULL,
	valid_until int(11) unsigned DEFAULT '0' NOT NULL,
	lead_days int(11) DEFAULT '2' NOT NULL,
	week_label varchar(32) DEFAULT '' NOT NULL
);

CREATE TABLE tx_digitalpageflip_domain_model_page (
	flipbook int(11) DEFAULT '0' NOT NULL,
	page_number int(11) DEFAULT '0' NOT NULL,
	image int(11) unsigned DEFAULT '0' NOT NULL,
	image_fallback int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL
);
