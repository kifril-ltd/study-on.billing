uplocal:
	@${COMPOSE} --env-file .env.local  up  -d

downlocal:
	@${COMPOSE} --env-file .env.local  down