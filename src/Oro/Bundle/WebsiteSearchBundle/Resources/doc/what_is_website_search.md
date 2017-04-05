What is website search and how it's different from regular search
=================================================================


### General information

The main purpose of the website search is to provide the customer with the ability to use search functionality at the application frontend. Website search should be used only at frontend because of its nature - the data is stored by websites (i.e. each
website has its own scope in the storage) and some frontend-specific values (like localization) 
are necessary for the frontend search use cases - e.g. user should have a possibility to search data only using one specific
localization.

Data for the website search index is collected and stored by websites and entity types. It means that each entity for
each website has its own scope in the storage, and as a consequence, these scopes are independent and can be handled
separately. For example, a developer might ask to reindex only specific entity for a specific website, and this change
does not affect any other entity at the specified website or any other website data.

Engine data collection is event based, so any bundle can mix its own information to search index. As a consequence,
some entities in the index might contain information that is not related directly, but is still valuable to search by
related areas.

By design, website indexation supports both synchronous and asynchronous operation. When triggering
reindexation, you can define whether it should run in the synchronous or asynchronous mode.
During the asynchronous reindexation, the appropriate message is put to the message queue and is processed by the consumer later by reindexing the required scope of entities.

### WebsiteSearchBundle VS SearchBundle

OroPlatform already contains a SearchBundle and this chapter describes the difference between SearchBundle and WebsiteSearchBundle.

The first and the main difference is the way index is stored. The website (frontend) index storage is separated from the platform index storage and may be moved to a separate server and thus may be properly scaled.

Next important difference is in the information they control. The platform index handles the backend information (e.g. management console), and the website
index contains information about the frontend (e.g. front store). As a consequence, platform index is usually smaller and the search and indexation speed is well balanced, while frontend index trades off the indexation speed for a faster search.

Though indexation might be a little slower comparing to backend index, frontend index is more flexible in terms of extendability. It is event based, and there are several events that
allow to customize different parts of search and indexation. 
