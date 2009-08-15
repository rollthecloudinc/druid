<?php
/*
* ActiveRecord will only communicate with find config through this interface
*/
interface IActiveRecordFindConfig {

	// allowed only for first/main argument
	const findInclude = 'include';
	const findLimit = 'limit';	
	const findOffset = 'offset';
	
	/*
	* Determines what specific fields to select from model. If not supplied all fields selected. regardless of whether
	* or not this is specified the primary key for the model will be selected.
	*/
	const findSelect = 'select';
	
	/*
	* determines what columns to omit from select if supplied
	*/
	const findNonSelect = 'deselect';
	
	/*
	* Any "made up" fields you would like to essentially overload into the model. For example, this may be used to add
	* a calculated field that uses fields from various included models. Ie. array('href'=>'Concat('<a href="',Bid.user_id,'">',Project.title,'</a>')')
	* The system will go through a replace the model names with the appropriate aliases if used in this way.
	*/
	const findDynamic = 'dynamic';
	
	/*
	* The main difference between a filter and a condition is that a filter can be transformed and the key name is the column with one
	* exception. Tat exception being that any ( or ) character are extracted and reapplied, then what is left is used as the column name. This
	* is done to allow grouping of conditions easilly. Ie. array('(id'=>array('? OR id=? OR id=?)',9,8,7)). Filters are also magical in the sense
	* that you need not specify a filter key. You may place filters directly in the argument array and anything that isn't a keyword
	* will assumed to be a filter. For example. array('limit'=>9,'id'=>10) - In this instance id will be extracted as a filter becasue limit
	* is a keyword for the finder mechanism.
	*/
	const findFilter = 'filter';
	
	/*
	* A condition is essentially the same as a filter but, allows precise control over input. A condition uses the keys
	* within as names of the condition. These need not relate to columns in the model though. They are just names which
	* may be refered to in the filterMap. The value of a condition has keys 0-2 (3). The first is the left side 
	* second operator third right side. If a array is used for either key 0 or 2 the first key inside that array is embedded
	* and the rest are bound. So you should use placeholders ? to determine where that bound data goes.
	* . Ie. 'condition'=>array('myFilter'=>array('Project.created','>=',array('FROM_UNIXTIME(?)','5')))
	* Conditions are not based on belonging to the model which the argument resides. Therefore, if you have included a blog_comment
	* instead of specifying a second argument array you may just use a condition and the model will be aliased as appropriate.
	* Ie. array('include'=>'blog_entry','condition'=>array('id'=>array('BlogEntry.id','=',9))).
	*/
	const findCondition = 'condition';
	
	const findSort = 'sort';
	const findGroup = 'group';
	const findHaving = 'having';
	
	/*
	* The join type for a related table. This is essentially ireelevant for the first table/main model
	*/
	const findJoinType = 'join';
	
	/*
	* Similar to findJoinType but this option is less specific and shouldbe a boolean. If the boolean is true
	* and the join type has not been declared then join type will default to inner. If the boolean is false
	* then the join type will default to left. However, if the joinType has been specified then this option
	* is essentially ignored becasue joinType option is more specific.
	*/
	const findRequireJoin = 'require';
	
	/*
	* Allows precise control over how conditions are placed together via name. This option
	* works alongside the condition option by using the names of the conditions and replacing them
	* with the actual condition values. Ie. 'filterMap'=>'({name} OR {name2})' This would look 
	* to the conditions and find conditions with the specified names then place then replace the name with the appropriate string
	* and use that as the filter. You may also pass a array for this option. The values that follow the first will be bound
	* to the query. Therefore, you would use ? placeholders in the filgterMap to specify where the bound data goes.
	*/
	const findConditionMap = 'conditionMap';
	
	const findInvisible = 'cloak';
	
	// deselects all columns including primary key. This is useful for subqueries where one
	// may only wish to return one column
	
	const findEmpty = 'empty';
	
	const findAssociation = 'association';
	const findAssociationPropertyName = 'rename';
	const findAssociationPropertyType = 'propertyType';
	
	/*
	* Will magically reference primary key regardless of its name. This makes
	* it possible to build clauses such as filters without actually knowing
	* the primary key field name. This can be done because the system relies
	* on the fact that one primary key field exists per table. Thus, making it a matter
	* of parsing for this "special" value and swapping it out with the actual
	* primary key name from the table config object. This feature has not been implemented
	* yet though.
	*/
	const id = ':pk';

	public function getInclude();
	public function getLimit();
	public function getOffset();
	public function getSelect();
	public function getNonSelect();
	public function getDynamic();
	public function getCondition();
	public function getConditionMap();
	public function getFilter();
	public function getGroup();
	public function getSort();
	public function getJoinType();
	public function getRequireJoin();
	public function getHaving();
	public function getMagicalFilter();
	public function getInvisible();
	public function getEmpty();
	public function getAssociation();
	public function getAssociationPropertyName();
	public function getAssociationPropertyType();
	
	public function hasInclude();
	public function hasLimit();
	public function hasOffset();
	public function hasSelect();
	public function hasNonSelect();
	public function hasDynamic();
	public function hasCondition();
	public function hasConditionMap();
	public function hasFilter();
	public function hasGroup();
	public function hasSort();
	public function hasHaving();
	public function hasJoinType();
	public function hasRequireJoin();
	public function hasMagicalFilter();
	public function hasInvisible();
	public function hasEmpty();
	public function hasAssociation();
	public function hasAssociationPropertyName();
	public function hasAssociationPropertyType();

}
?>