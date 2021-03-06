<?php

// Load the Repo, and Repo extensions
if ( !empty( $wmgUseWikibaseRepo ) ) {
	wfLoadExtension( 'WikibaseRepository', "$IP/extensions/Wikibase/extension-repo.json" );
	if ( !empty( $wmgUseWikibaseWikidataOrg ) ) {
		wfLoadExtension( 'Wikidata.org' );
	}
	if ( !empty( $wmgUseWikibasePropertySuggester ) ) {
		wfLoadExtension( 'PropertySuggester' );
	}
	if ( !empty( $wmgUseWikibaseQuality ) ) {
		wfLoadExtension( 'WikibaseQualityConstraints' );
	}
	if ( !empty( $wmgUseWikibaseLexeme ) ) {
		wfLoadExtension( 'WikibaseLexeme' );
	}
	if ( !empty( $wmgUseWikibaseMediaInfo ) ) {
		wfLoadExtension( 'WikibaseMediaInfo' );
		$wgWBRepoSettings['enableEntitySearchUI'] = false;
	}
	if ( !empty( $wmgUseWikibaseCirrusSearch ) ) {
		wfLoadExtension( 'WikibaseCirrusSearch' );
	}
	if ( !empty( $wmgUseWikibaseLexemeCirrusSearch ) ) {
		wfLoadExtension( 'WikibaseLexemeCirrusSearch' );
	}
}

// Load the Client, and Client extensions
if ( !empty( $wmgUseWikibaseClient ) ) {
	wfLoadExtension( 'WikibaseClient', "$IP/extensions/Wikibase/extension-client.json" );
	if ( !empty( $wmgUseWikibaseWikimediaBadges ) ) {
		wfLoadExtension( 'WikimediaBadges' );
	}
	if ( !empty( $wmgUseArticlePlaceholder ) ) {
		wfLoadExtension( 'ArticlePlaceholder' );
	}
	if ( !empty( $wmgUseWikibaseLexeme ) ) {
		wfLoadExtension( 'WikibaseLexeme' );
	}
}

// Define the namespace indexes for repos and clients
// NOTE: do *not* define WB_NS_ITEM and WB_NS_ITEM_TALK when using a core namespace for items!!..
define( 'WB_NS_PROPERTY', 120 );
define( 'WB_NS_PROPERTY_TALK', 121 );
define( 'WB_NS_LEXEME', 146 );
define( 'WB_NS_LEXEME_TALK', 147 );
// TODO the query namespace is not actually used in prod. Remove me?
define( 'WB_NS_QUERY', 122 );
define( 'WB_NS_QUERY_TALK', 123 );

// This allows cache invalidations to be in sync with deploys
// and not shared across different versions of wikibase.
// e.g. wikibase_shared/1_31_0-wmf_2-testwikidatawiki0 for test wikis
// and wikibase_shared/1_31_0-wmf_2-wikidatawiki for all others.
$wmgWBSharedCacheKey = 'wikibase_shared/' . str_replace( '.', '_', $wmgVersionNumber ) . '-' . $wmgWikibaseCachePrefix;

// Lock manager config must use the master datacenter
// Use a TTL of 15 mins, no script will run for longer than this
$wgLockManagers[] = [
	'name'         => 'wikibaseDispatchRedisLockManager',
	'class'        => 'RedisLockManager',
	'lockTTL'      => 900, // 15 mins ( 15 * 60 )
	'lockServers'  => $wmfMasterServices['redis_lock'],
	'domain'       => $wgDBname,
	'srvsByBucket' => [
		0 => $redisLockServers
	],
	'redisConfig'  => [
		'connectTimeout' => 2,
		'readTimeout'    => 2,
		'password'       => $wmgRedisPassword
	]
];

if ( $wmgUseWikibaseRepo ) {
	if ( $wgDBname === 'wikidatawiki' ) {
		// Disable Special:ItemDisambiguation on wikidata.org T195756
		$wgSpecialPages['ItemDisambiguation'] = 'SpecialBlankpage';
	}

	if ( $wgDBname === 'wikidatawiki' || $wgDBname === 'testwikidatawiki' ) {
		// Don't try to let users answer captchas if they try to add links
		// on either Item or Property pages. T86453
		$wgCaptchaTriggersOnNamespace[NS_MAIN]['addurl'] = false;
		$wgCaptchaTriggersOnNamespace[WB_NS_PROPERTY]['addurl'] = false;

		// T53637 and T48953
		$wgGroupPermissions['*']['property-create'] = ( $wgDBname === 'testwikidatawiki' );
	}

	// Load wikibase search settings
	require_once "{$wmfConfigDir}/SearchSettingsForWikibase.php";

	// Calculate the client Db lists based on our wikiversions db lists
	if ( $wgDBname === 'testwikidatawiki' ) {
		$wgWBRepoSettings['localClientDatabases'] = [ 'testwiki', 'test2wiki', 'testwikidatawiki' ];
	} elseif ( $wgDBname === 'wikidatawiki' ) {
		$wgWBRepoSettings['localClientDatabases'] = array_diff(
			MWWikiversions::readDbListFile( 'wikidataclient' ),
			[ 'testwikidatawiki', 'testwiki', 'test2wiki' ]
		);
		// Exclude closed wikis
		$wgWBRepoSettings['localClientDatabases'] = array_diff(
			$wgWBRepoSettings['localClientDatabases'],
			MWWikiversions::readDbListFile( $wmfRealm === 'labs' ? 'closed-labs' : 'closed' )
		);
		// Exclude non-existent wikis in labs
		if ( $wmfRealm === 'labs' ) {
			$wgWBRepoSettings['localClientDatabases'] = array_intersect(
				MWWikiversions::readDbListFile( 'all-labs' ),
				$wgWBRepoSettings['localClientDatabases']
			);
		}
	} elseif ( $wgDBname === 'commonswiki' || $wgDBname === 'testcommonswiki' ) {
		$wgWBRepoSettings['localClientDatabases'] = [];
	}

	// Reshaping the value, it's needed
	$wgWBRepoSettings['localClientDatabases'] = array_combine(
		$wgWBRepoSettings['localClientDatabases'],
		$wgWBRepoSettings['localClientDatabases']
	);

	$wgWBRepoSettings['entityNamespaces'] = $wmgWikibaseRepoEntityNamespaces;
	$wgWBRepoSettings['disabledDataTypes'] = $wmgWikibaseDisabledDataTypes;
	$wgWBRepoSettings['entityDataFormats'] = $wmgWikibaseEntityDataFormats;
	$wgWBRepoSettings['maxSerializedEntitySize'] = $wmgWikibaseMaxSerializedEntitySize;
	$wgWBRepoSettings['siteLinkGroups'] = $wmgWBSiteLinkGroups;
	$wgWBRepoSettings['specialSiteLinkGroups'] = $wmgWikibaseRepoSpecialSiteLinkGroups;
	$wgWBRepoSettings['statementSections'] = $wmgWikibaseRepoStatementSections;
	$wgWBRepoSettings['badgeItems'] = $wmgWikibaseRepoBadgeItems;
	$wgWBRepoSettings['preferredGeoDataProperties'] = $wmgWBRepoPreferredGeoDataProperties;
	$wgWBRepoSettings['preferredPageImagesProperties'] = $wmgWBRepoPreferredPageImagesProperties;
	$wgWBRepoSettings['conceptBaseUri'] = $wmgWBRepoConceptBaseUri;

	$wgWBRepoSettings['entitySources'] = $wmgWikibaseEntitySources;
	if ( isset( $wmgWikibaseRepoLocalEntitySourceName ) ) {
		$wgWBRepoSettings['localEntitySourceName'] = $wmgWikibaseRepoLocalEntitySourceName;
	}

	// Various settings have null / no setting yet for various sites,
	// so we need to check they are set before trying to use them to avoid warnings.
	if ( isset( $wmgWBRepoSettingsSparqlEndpoint ) ) {
		$wgWBRepoSettings['sparqlEndpoint'] = $wmgWBRepoSettingsSparqlEndpoint;
	}
	if ( isset( $wmgWBRepoFormatterUrlProperty ) ) {
		$wgWBRepoSettings['formatterUrlProperty'] = $wmgWBRepoFormatterUrlProperty;
	}
	if ( isset( $wmgWBRepoCanonicalUriProperty ) ) {
		$wgWBRepoSettings['canonicalUriProperty'] = $wmgWBRepoCanonicalUriProperty; // T178180
	}

	if ( isset( $wmgWBRepoIdGenerator ) ) {
		$wgWBRepoSettings['idGenerator'] = $wmgWBRepoIdGenerator; // T194299
	}

	$wgWBRepoSettings['dataRightsText'] = 'Creative Commons CC0 License';
	$wgWBRepoSettings['dataRightsUrl'] = 'https://creativecommons.org/publicdomain/zero/1.0/';

	$wgWBRepoSettings['dataCdnMaxAge'] = 1 * 60 * 60;
	$wgWBRepoSettings['sharedCacheDuration'] = 60 * 60 * 24;
	$wgWBRepoSettings['sharedCacheKeyGroup'] = $wmgWikibaseCachePrefix;
	$wgWBRepoSettings['sharedCacheKeyPrefix'] = $wmgWBSharedCacheKey;

	// T189776, T189777
	$wgWBRepoSettings['useTermsTableSearchFields'] = false;

	// These settings can be overridden by the cron parameters in operations/puppet
	$wgWBRepoSettings['dispatchingLockManager'] = $wmgWikibaseDispatchingLockManager;
	$wgWBRepoSettings['dispatchDefaultDispatchInterval'] = $wmgWikibaseDispatchInterval;
	$wgWBRepoSettings['dispatchMaxTime'] = $wmgWikibaseDispatchMaxTime;
	$wgWBRepoSettings['dispatchDefaultBatchSize'] = $wmgWikibaseDispatchDefaultBatchSize;
	$wgWBRepoSettings['dispatchLagToMaxLagFactor'] = 60;

	$wgWBRepoSettings['unitStorage'] = [
		'class' => '\\Wikibase\\Lib\\Units\\JsonUnitStorage',
		'args' => [ __DIR__ . '/unitConversionConfig.json' ]
	];

	if ( isset( $wmgWikibaseUseSSRTermbox ) && isset( $wmgWikibaseSSRTermboxServerUrl ) ) {
		$wgWBRepoSettings['termboxEnabled'] = $wmgWikibaseUseSSRTermbox;
		$wgWBRepoSettings['ssrServerUrl'] = $wmgWikibaseSSRTermboxServerUrl;
	}

	if ( isset( $wmgWikibaseUseSSRTermboxDesktop ) ) {
		$wgWBRepoSettings['termboxDesktopEnabled'] = $wmgWikibaseUseSSRTermboxDesktop;
	}

	if ( isset( $wmgWikibaseTaintedReferencesEnabled ) ) {
		$wgWBRepoSettings['taintedReferencesEnabled'] = $wmgWikibaseTaintedReferencesEnabled;
	}

	if ( isset( $wmgWikibaseStringLimits ) ) {
		$wgWBRepoSettings['string-limits'] = $wmgWikibaseStringLimits;
	}

	// Federation temporarily commented out, see T211237 & T204748
	$wgWBRepoSettings['foreignRepositories'] = $wmgWikibaseRepoForeignRepositories;

	// Temporary, see T184933
	$wgWBRepoSettings['useKartographerGlobeCoordinateFormatter'] = true;

	$wgWBRepoSettings['idGeneratorSeparateDbConnection'] = true;

	$wgWBRepoSettings['entityTypesWithoutRdfOutput'] = $wmgWikibaseEntityTypesWithoutRdfOutput;

	// Migration, T226086
	$wgWBRepoSettings['tmpPropertyTermsMigrationStage'] = $wmgWikibaseTmpPropertyTermsMigrationStage;
	$wgWBRepoSettings['tmpItemTermsMigrationStages'] = $wmgWikibaseTmpItemTermsMigrationStage;

	// T235033
	if ( isset( $wmgWikibaseRepoDataBridgeEnabled ) ) {
		$wgWBRepoSettings['dataBridgeEnabled'] = $wmgWikibaseRepoDataBridgeEnabled;
	}

	$wgWBRepoSettings['enableRefTabs'] = $wmgWikibaseRepoEnableRefTabs;

	$wgWBRepoSettings['propagateChangeVisibility'] = $wmgWikibaseRepoPropagateChangeVisibility;

	// entity data for URLs matching these patterns will be cached in Varnish and purged if needed;
	// all other entity data URLs will receive no caching
	$wgWBRepoSettings['entityDataCachePaths'] = [
		// // JSON from entity page JS, compare wikibase.entityPage.entityLoaded.js
		'/wiki/Special:EntityData/{entity_id}.json?revision={revision_id}',
		// Turtle from Query Service updater, compare WikibaseRepository.java
		'/wiki/Special:EntityData/{entity_id}.ttl?flavor=dump&revision={revision_id}',
		// third pattern with high volume of requests in Hive, source unknown
		'/wiki/Special:EntityData?id={entity_id}&revision={revision_id}&format=json',
	];
}

if ( $wmgUseWikibaseClient ) {
	$wbSiteGroup = isset( $wmgWikibaseSiteGroup ) ? $wmgWikibaseSiteGroup : null;
	$wgWBClientSettings['languageLinkSiteGroup'] = $wbSiteGroup;

	if ( in_array( $wgDBname, [ 'commonswiki', 'mediawikiwiki', 'metawiki', 'specieswiki', 'wikimaniawiki' ] ) ) {
		$wgWBClientSettings['languageLinkSiteGroup'] = 'wikipedia';
	}

	$wgWBClientSettings['siteGroup'] = $wbSiteGroup;

	if ( $wgDBname === 'wikidatawiki' || $wgDBname === 'testwikidatawiki' ) {
		$wgWBClientSettings['languageLinkSiteGroup'] = 'wikipedia';
		$wgWBClientSettings['showExternalRecentChanges'] = false;
	}
	// TODO clean up slightly messy client only config in this condition above this line

	// to be safe, keeping this here although $wgDBname is default setting
	$wgWBClientSettings['siteGlobalID'] = $wgDBname;

	$wgWBClientSettings['entityNamespaces'] = $wmgWikibaseClientEntityNamespaces;
	$wgWBClientSettings['repoNamespaces'] = $wmgWikibaseClientRepoNamespaces;
	$wgWBClientSettings['namespaces'] = $wmgWikibaseClientNamespacesWithRepoAccess;

	$wgWBClientSettings['excludeNamespaces'] = function () {
		global $wgDBname, $wgProofreadPageNamespaceIds;

		// @fixme 102 is LiquidThread comments on wikinews and elsewhere?
		// but is the Extension: namespace on mediawiki.org, so we need
		// to allow wiki-specific settings here.
		$excludeNamespaces = array_merge(
			MWNamespace::getTalkNamespaces(),
			// 90 => LiquidThread threads
			// 92 => LiquidThread summary
			// 118 => Draft
			// 1198 => NS_TRANSLATE
			// 2600 => Flow topic
			[ NS_USER, NS_FILE, NS_MEDIAWIKI, 90, 92, 118, 1198, 2600 ],
			array_values( $wgProofreadPageNamespaceIds )
		);

		if ( in_array( $wgDBname, MWWikiversions::readDbListFile( 'wiktionary' ) ) ) {
			$excludeNamespaces[] = NS_MAIN;
			$excludeNamespaces[] = 114; // citations ns
		}

		return $excludeNamespaces;
	};

	// IS-labs.php has null as the default beta doesn't have a defined property order url.
	// To avoid an undefined variable wrap this in a condition.
	if ( isset( $wmgWikibaseClientPropertyOrderUrl ) ) {
		$wgWBClientSettings['propertyOrderUrl'] = $wmgWikibaseClientPropertyOrderUrl;
	}

	$wgWBClientSettings['changesDatabase'] = $wmgWikibaseClientChangesDatabase;
	$wgWBClientSettings['repoDatabase'] = $wmgWikibaseClientRepoDatabase;
	$wgWBClientSettings['repoUrl'] = $wmgWikibaseClientRepoUrl;
	$wgWBClientSettings['repoConceptBaseUri'] = $wmgWikibaseClientRepoConceptBaseUri;
	$wgWBClientSettings['repositories'] = $wmgWikibaseClientRepositories;
	$wgWBClientSettings['wikiPageUpdaterDbBatchSize'] = 20;

	$wgWBClientSettings['disabledAccessEntityTypes'] = $wmgWikibaseDisabledAccessEntityTypes;
	$wgWBClientSettings['maxSerializedEntitySize'] = $wmgWikibaseMaxSerializedEntitySize;

	$wgWBClientSettings['siteLinkGroups'] = $wmgWBSiteLinkGroups;
	$wgWBClientSettings['specialSiteLinkGroups'] = $wmgWikibaseClientSpecialSiteLinkGroups;
	$wgWBClientSettings['badgeClassNames'] = $wmgWikibaseClientBadgeClassNames;

	$wgWBClientSettings['allowLocalShortDesc'] = $wmgWikibaseAllowLocalShortDesc;
	$wgWBClientSettings['allowDataTransclusion'] = $wmgWikibaseEnableData;
	$wgWBClientSettings['allowDataAccessInUserLanguage'] = $wmgWikibaseAllowDataAccessInUserLanguage;
	$wgWBClientSettings['entityAccessLimit'] = $wmgWikibaseEntityAccessLimit;
	$wgWBClientSettings['injectRecentChanges'] = $wmgWikibaseClientInjectRecentChanges;

	$wgWBClientSettings['sharedCacheKeyGroup'] = $wmgWikibaseCachePrefix;
	$wgWBClientSettings['sharedCacheKeyPrefix'] = $wmgWBSharedCacheKey;
	$wgWBClientSettings['sharedCacheDuration'] = 60 * 60 * 24;

	$wgWBClientSettings['trackLuaFunctionCallsPerSiteGroup'] = $wmgWikibaseClientTrackLuaFunctionCallsPerSiteGroup;
	$wgWBClientSettings['trackLuaFunctionCallsPerWiki'] = $wmgWikibaseClientTrackLuaFunctionCallsPerWiki;

	// T142103
	$wgWBClientSettings['sendEchoNotification'] = true;
	$wgWBClientSettings['echoIcon'] = $wmgWikibaseClientEchoIcon;

	$wgWBClientSettings['useTermsTableSearchFields'] = $wmgWikibaseClientUseTermsTableSearchFields;

	$wgWBClientSettings['disabledUsageAspects'] = $wmgWikibaseDisabledUsageAspects;
	$wgWBClientSettings['fineGrainedLuaTracking'] = $wmgWikibaseFineGrainedLuaTracking;
	$wgWBClientSettings['entityUsageModifierLimits'] = [ 'D' => 10, 'L' => 10, 'C' => 33 ];

	// T208763
	if ( isset( $wmgWikibaseClientPageSchemaNamespaces ) ) {
		$wgWBClientSettings['pageSchemaNamespaces'] = $wmgWikibaseClientPageSchemaNamespaces;
	}

	$wgWBClientSettings['entitySources'] = $wmgWikibaseEntitySources;
	if ( isset( $wmgWikibaseClientItemAndPropertySourceName ) ) {
		$wgWBClientSettings['itemAndPropertySourceName'] = $wmgWikibaseClientItemAndPropertySourceName;
	}

	$wgWBClientSettings['addEntityUsagesBatchSize'] = $wmgWikibaseClientAddEntityUsagesBatchSize;

	// Temporary, see T210926
	$wgWBClientSettings['useKartographerMaplinkInWikitext'] = true;

	// Migration, T226086
	$wgWBClientSettings['tmpPropertyTermsMigrationStage'] = $wmgWikibaseTmpPropertyTermsMigrationStage;
	$wgWBClientSettings['tmpItemTermsMigrationStages'] = $wmgWikibaseTmpItemTermsMigrationStage;

	// T226816
	if ( isset( $wmgWikibaseClientDataBridgeEnabled ) && isset( $wmgWikibaseClientDataBridgeHrefRegExp ) ) {
		$wgWBClientSettings['dataBridgeEnabled'] = $wmgWikibaseClientDataBridgeEnabled;
		$wgWBClientSettings['dataBridgeHrefRegExp'] = $wmgWikibaseClientDataBridgeHrefRegExp;
		if ( isset( $wmgWikibaseClientDataBridgeEditTags ) ) {
			$wgWBClientSettings['dataBridgeEditTags'] = $wmgWikibaseClientDataBridgeEditTags;
		}
		if ( isset( $wmgWikibaseClientWellKnownReferencePropertyIds ) ) {
			$wgWBClientSettings['wellKnownReferencePropertyIds'] = $wmgWikibaseClientWellKnownReferencePropertyIds;
		}
	}
}

unset( $wmgWBSharedCacheKey );
