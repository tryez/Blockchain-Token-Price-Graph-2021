<?php

class DB {
	
	public static $pdo = null;


	public static function setConnection($pdo){
		self::$pdo = $pdo;
	}


	public static function select($query, $params = null){
	    $stmt = self::$pdo->prepare($query);
	    $stmt->execute($params ?? []);
	    return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function selectOne($query, $params = null){
	    $stmt = self::$pdo->prepare($query);
	    $stmt->execute($params ?? []);
	    return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function update($query, $params = null){
	    $stmt = self::$pdo->prepare($query);
	    return $stmt->execute($params ?? []);
	}

	public static function raw($query, $params = null){
	    $stmt = self::$pdo->prepare($query);
	    return $stmt->execute($params ?? []);
	}

}