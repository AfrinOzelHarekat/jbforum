<?php

namespace XF\Job;

class ReactionScore extends AbstractRebuildJob
{
	protected $defaultData = [
		'reaction_id' => null
	];

	protected function getNextIds($start, $batch)
	{
		$db = $this->app->db();

		if ($this->data['reaction_id'])
		{
			return $db->fetchAllColumn($db->limit(
				"
                    SELECT DISTINCT content_user_id
                    FROM xf_reaction_content
                    WHERE
                          reaction_id=? AND
                          content_user_id > ?
                    ORDER BY content_user_id", $batch
			), [
				$this->data['reaction_id'], $start
			]);
		}
		else
		{
			return $db->fetchAllColumn($db->limit(
				"
				SELECT user_id
				FROM xf_user
				WHERE user_id > ?
				ORDER BY user_id
			", $batch
			), $start);
		}
	}

	protected function rebuildById($id)
	{
		$db = $this->app->db();

		/** @var \XF\Repository\Reaction $reactionRepo */
		$reactionRepo = $this->app->repository('XF:Reaction');

		$db->beginTransaction();
		$count = $reactionRepo->getUserReactionScore($id);

		$this->app->db()->update('xf_user', ['reaction_score' => $count], 'user_id = ?', $id);
		$db->commit();
	}

	protected function getStatusType()
	{
		return \XF::phrase('reaction_score');
	}
}