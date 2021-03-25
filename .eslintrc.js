module.exports = {
	parser: '@typescript-eslint/parser',
	parserOptions: {
		ecmaVersion: 2020,
		sourceType: 'module',
	},
	extends: [
		'plugin:@typescript-eslint/recommended',
		'prettier/@typescript-eslint',
		'plugin:prettier/recommended'
	],

	env: {
		node: true,
		es6: true,
		serviceworker: true,
		browser: true,
	},
	globals: {
		workbox: false,
		document: true,
		window: true,
		jQuery: true,
		$: true,
		stateObj:true

	},


	rules: {
		//"security/detect-non-literal-require": 0,
		'prettier/prettier': 'error',
		'prefer-const': 0,
		// Typescript rules
		'@typescript-eslint/no-var-requires': 0,
		'@typescript-eslint/camelcase': 0,
		'@typescript-eslint/ban-ts-ignore': 0,
		'@typescript-eslint/no-unused-vars': 0,
		'@typescript-eslint/no-explicit-any': 0,
		'@typescript-eslint/ban-ts-comment': 0,
		'@typescript-eslint/explicit-function-return-type': 0,
		'@typescript-eslint/no-this-alias': 0,
		'@typescript-eslint/no-shadow': 1,
		'@typescript-eslint/triple-slash-reference': [
			0,
			{
				path: 'always',
				types: 'prefer-import',
				lib: 'prefer-import',
			},
		],
		// @typescript-eslint/explicit-function-return-type": 0,


		//  eslint-disable @typescript-eslint/no-unused-vars
		//  eslint-disable @typescript-eslint/explicit-function-return-type

		//
		//Possible Errors
		//
		// The following rules point out areas where you might have made mistakes.
		//
		'comma-dangle': 0, // disallow or enforce trailing commas
		'no-cond-assign': 2, // disallow assignment in conditional expressions
		'no-console': 0, // disallow use of console (off by default in the node environment)
		'no-constant-condition': 2, // disallow use of constant expressions in conditions
		'no-control-regex': 2, // disallow control characters in regular expressions
		'no-debugger': 2, // disallow use of debugger
		'no-dupe-args': 2, // disallow duplicate arguments in functions
		'no-dupe-keys': 2, // disallow duplicate keys when creating object literals
		'no-duplicate-case': 2, // disallow a duplicate case label.
		'no-empty': 2, // disallow empty statements

		'no-ex-assign': 2, // disallow assigning to the exception in a catch block
		'no-extra-boolean-cast': 2, // disallow double-negation boolean casts in a boolean context
		'no-extra-parens': 0, // disallow unnecessary parentheses (off by default)
		'no-extra-semi': 2, // disallow unnecessary semicolons
		'no-func-assign': 2, // disallow overwriting functions written as function declarations
		'no-inner-declarations': 2, // disallow function or variable declarations in nested blocks
		'no-invalid-regexp': 2, // disallow invalid regular expression strings in the RegExp constructor
		'no-irregular-whitespace': 2, // disallow irregular whitespace outside of strings and comments
		'no-negated-in-lhs': 2, // disallow negation of the left operand of an in expression
		'no-obj-calls': 2, // disallow the use of object properties of the global object (Math and JSON) as functions
		'no-regex-spaces': 2, // disallow multiple spaces in a regular expression literal
		'no-sparse-arrays': 2, // disallow sparse arrays
		'no-unreachable': 2, // disallow unreachable statements after a return, throw, continue, or break statement
		'use-isnan': 2, // disallow comparisons with the value NaN
		'valid-jsdoc': 0, // Ensure JSDoc comments are valid (off by default)
		'valid-typeof': 2, // Ensure that the results of typeof are compared against a valid string
	},
};
