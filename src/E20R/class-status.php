<?php
/**
 * Copyright (c) 2018-2019. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Copyright (c) 2019. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Import_Members;


class Status {
	
	const E20R_ERROR_NO_USER_ID = 4;
	const E20R_ERROR_NO_EMAIL = 3;
	const E20R_ERROR_NO_LOGIN = 2;
	const E20R_ERROR_USER_NOT_FOUND = 10;
	
	const E20R_ERROR_NO_UPDATE_FROM_EMAIL = 100;
	const E20R_ERROR_NO_UPDATE_FROM_LOGIN = 101;
	const E20R_ERROR_NO_UPDATE_FROM_ID = 102;
	const E20R_ERROR_USER_EXISTS_NO_UPDATE = 103;
	
	const E20R_ERROR_ID_NOT_NUMBER = 200;
	const E20R_ERROR_UPDATE_NEEDED_NOT_ALLOWED = 201;
}